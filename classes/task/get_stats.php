<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tasks
 *
 * @package     local_ace
 * @copyright   2021 University of Canterbury
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ace\task;

/**
 * get_stats class, used to get stats for indicators.
 */
class get_stats extends \core\task\scheduled_task {
    /** @var int $insertemptyengagementrecords - should we insert empty engagement records.? */
    public $insertemptyengagementrecords = true;

    /** @var boolean $onlyaceperiod - should only the ace period stats be calculated. */
    public $onlyaceperiod = false;

    /** @var boolean $deleteexisting - allows existing data to be cleared before regeneration. */
    public $deleteexisting = false;

    /**
     * Returns the name of this task.
     */
    public function get_name() {
        // Shown in admin screens.
        return get_string('getstats', 'local_ace');
    }

    /**
     * Executes task.
     */
    public function execute() {
        global $DB;
        $runlast = get_config('local_ace', 'statsrunlast');
        $displayperiod = get_config('local_ace', 'displayperiod');
        $calclifetime = get_config('analytics', 'calclifetime');

        if (empty($runlast)) {
            // Only do last 6 months of data for first run.
            $runlast = time() - (DAYSECS * 30 * 6);
        }
        $now = time();
        $onlyacesql = '';
        if ($this->onlyaceperiod) {
            $onlyacesql = " AND c.endtime - c.starttime = " . get_config('local_ace', 'displayperiod');
        }
        // Get user stats for each context (course) for indicators that we care about.
        // cognitive and social breadth are stored as values between-1 -> 1
        // Exclude potential cognitive/social as these are not user indicators.
        // To convert these to percentages we do: 100 * (var +1)/2.
        // Ignore when only 1 indicator present - this is likely a different process like no course access.

        $sql = 'SELECT DISTINCT c.starttime, c.endtime, c.contextid, ue.userid, count(value) as cnt, SUM((value + 1)/2) as value
                  FROM {analytics_indicator_calc} c
                  JOIN {user_enrolments} ue on ue.id = c.sampleid
                 WHERE c.timecreated > :runlast ' . $onlyacesql . '
                       AND sampleorigin = \'user_enrolments\'
                       AND (indicator like \'%cognitive_depth\'
                            OR indicator like \'%social_breadth\'
                            OR indicator like \'%any_course_access\'
                            OR indicator like \'%read_actions\'
                            OR indicator like \'%local_echo360analytics%\')
                       AND indicator not like \'%potential%\'
              GROUP BY c.starttime, c.endtime, c.contextid, ue.userid, c.sampleid';

        $indicators = $DB->get_recordset_sql($sql, array('runlast' => $runlast));

        if ($this->deleteexisting) {
            $from = $now - ($calclifetime * DAYSECS);
            mtrace("Analytics set to delete ace samples" . $calclifetime . " days");
            $DB->delete_records_select('local_ace_samples', 'starttime > ? AND endtime - starttime = ?', [$from, $displayperiod]);
        }

        $newsamples = array();
        foreach ($indicators as $indicator) {

            $sample = new \stdClass();
            $sample->starttime = $indicator->starttime;
            $sample->endtime = $indicator->endtime;
            $sample->contextid = $indicator->contextid;
            $sample->userid = $indicator->userid;

            if (empty(floatval($indicator->value))) {
                $sample->value = 0;
            } else {
                $sample->value = floatval($indicator->value) / $indicator->cnt; // Get average percentage.
            }
            $newsamples[] = $sample;
            if (count($newsamples) >= 1000) {
                $DB->insert_records('local_ace_samples', $newsamples);
                $newsamples = array();
            }
        }
        $indicators->close();

        if (!empty($newsamples)) {
            $DB->insert_records('local_ace_samples', $newsamples);
        }

        // TODO: Does this really need to be a 2nd DB call? - or can we generate it from above?

        // Now get average for each context id in the same timeframe.
        $sql = 'SELECT c.starttime, c.endtime, c.contextid, count(value) as cnt, SUM((value + 1)/2) as value
                  FROM {analytics_indicator_calc} c
                  JOIN {user_enrolments} ue on ue.id = c.sampleid
                 WHERE c.timecreated > :runlast ' . $onlyacesql . '
                       AND sampleorigin = \'user_enrolments\'
                       AND (indicator like \'%cognitive_depth\'
                            OR indicator like \'%social_breadth\'
                            OR indicator like \'%any_course_access\'
                            OR indicator like \'%read_actions\'
                            OR indicator like \'%local_echo360analytics%\')
                       AND indicator not like \'%potential%\'
              GROUP BY c.starttime, c.endtime, c.contextid';

        $indicators = $DB->get_recordset_sql($sql, array('runlast' => $runlast));

        if ($this->deleteexisting) {
            mtrace("Analytics set to delete ace contexts" . $calclifetime . " days");
            $from = $now - ($calclifetime * DAYSECS);
            $DB->delete_records_select('local_ace_contexts', 'starttime > ? AND endtime - starttime = ?', [$from, $displayperiod]);
        }

        $newsamples = array();
        foreach ($indicators as $indicator) {

            $sample = new \stdClass();
            $sample->starttime = $indicator->starttime;
            $sample->endtime = $indicator->endtime;
            $sample->contextid = $indicator->contextid;

            if (empty(floatval($indicator->value))) {
                $sample->value = 0;
            } else {
                $sample->value = floatval($indicator->value) / $indicator->cnt; // Get average percentage.
            }
            $newsamples[] = $sample;
            if (count($newsamples) >= 1000) {
                $DB->insert_records('local_ace_contexts', $newsamples);
                $newsamples = array();
            }
        }
        $indicators->close();

        if (!empty($newsamples)) {
            $DB->insert_records('local_ace_contexts', $newsamples);
        }

        if ($this->insertemptyengagementrecords) {
            // Create empty records for all user enrolments with no analytics during that specified time period.
            // Get unique course and timestart timeend records for users that are not already listed.
            $sql = "SELECT DISTINCT sa.starttime, sa.endtime, sa.contextid, ue.userid as userid
                      FROM {local_ace_samples} sa
                      JOIN {context} cx ON cx.id = sa.contextid AND cx.contextlevel = " . CONTEXT_COURSE . "
                      JOIN {enrol} e ON e.courseid = cx.instanceid
                      JOIN {user_enrolments} ue on ue.enrolid = e.id
                      LEFT JOIN {local_ace_samples} saj ON saj.starttime = sa.starttime
                      AND saj.endtime = sa.endtime AND saj.contextid = sa.contextid AND saj.userid = ue.userid
                     WHERE saj.id is null AND sa.starttime > :runlast AND sa.endtime - sa.starttime = :displayperiod";
            $recordset = $DB->get_recordset_sql($sql, ['runlast' => $runlast,
                'displayperiod' => get_config('local_ace', 'displayperiod')]);
            $count = 0;
            $emptysamples = [];
            foreach ($recordset as $record) {
                $sample = new \stdClass();
                $sample->starttime = $record->starttime;
                $sample->endtime = $record->endtime;
                $sample->contextid = $record->contextid;
                $sample->userid = $record->userid;
                $sample->value = 0;

                $emptysamples[] = $sample;
                $count++;
            }
            $recordset->close();
            if (!empty($emptysamples)) {
                $DB->insert_records('local_ace_samples', $emptysamples);
                mtrace("Added $count empty user enrolment values");
            }
        }

        set_config('statsrunlast', $now, 'local_ace');
    }
}

