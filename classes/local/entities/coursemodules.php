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

declare(strict_types=1);

namespace local_ace\local\entities;

use core\event\course_content_deleted;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\filters\boolean_select;
use core_reportbuilder\local\filters\number;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use core_reportbuilder\local\entities\base;
use local_ace\local\filters\coursemoduletype;
use lang_string;
use stdClass;
use html_writer;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/local/ace/lib.php');
require_once($CFG->dirroot.'/local/ace/locallib.php');

/**
 * Course entity class implementation
 *
 * This entity defines all the course columns and filters to be used in any report.
 *
 * @package     local_ace
 * @copyright   2021 University of Canterbury
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coursemodules extends base {
    /**
     * Custom aliases for the logstore_standard_log table as we can't define multiple per entity using the API.
     * We need separate aliases for each in case the multiple columns are added to the same report.
     */
    /** @var string */
    private $logstorealias1 = "cmlsls1";
    /** @var string */
    private $logstorealias2 = "cmlsls2";
    /** @var string */
    private $logstorealias3 = "cmlsls3";
    /** @var string */
    private $logstorealias4 = "cmlsls4";
    /** @var string */
    private $logstorealias5 = "cmlsls5";
    /** @var string */
    private $logstorealias6 = "cmlsls6";

    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {
        return [
                'user' => 'au',
                'enrol' => 'ae',
                'user_enrolments' => 'aue',
                'course' => 'ac',
                'course_modules' => 'acm',
                'modules' => 'am',
                'assign' => 'aa',
                'assign_submission' => 'aas',
                'logstore_standard_log' => 'alsl',
                'context' => 'actx',
                'totalviewcount' => 'cmtvc',
                'totalviewcountuser' => 'cmtvcu',
               ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('pluginname', 'local_ace');
    }

    /**
     * Initialise the entity, add all user fields and all 'visible' user profile fields
     *
     * @return base
     */
    public function initialise(): base {

        $columns = $this->get_all_columns();
        foreach ($columns as $column) {
            $this->add_column($column);
        }

        $filters = $this->get_all_filters();
        foreach ($filters as $filter) {
            $this
                ->add_filter($filter)
                ->add_condition($filter);
        }

        return $this;
    }

    /**
     * Returns list of all available columns
     *
     * These are all the columns available to use in any report that uses this entity.
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        // Note this custom report source is restricted to showing activities.
        $course = local_ace_get_course_helper();
        if (!empty($course)) {
            $courseid = $course->id;
        } else {
            $courseid = 0; // Should not happen when using this entity correctly, set to 0 to prevent SQL dying.
        }

        // Determine which user to use within the user specific columns - use $PAGE->context if user context or global $USER.
        $userid = local_ace_get_user_helper();

        $cmalias = $this->get_table_alias('course_modules');
        $modulesalias = $this->get_table_alias('modules');
        $totalviewcountalias = $this->get_table_alias('totalviewcount');
        $totalviewcountuseralias = $this->get_table_alias('totalviewcountuser');

        $this->add_join("JOIN {modules} {$modulesalias} ON {$cmalias}.module = {$modulesalias}.id");

        // Get list of modules we want to include in this query.
        $modules = \local_ace_get_module_types();

        // Create a table with the instanceid, module id and activity name to match with coursemodule table.
        $modulejoins = [];
        foreach ($modules as $mid => $mname) {
            $duedatecolumn = 0; // Where the activity doesn't have a duedate we prefill this param as empty.
            if ($mname == 'assign') {
                $duedatecolumn = 'duedate';
            }
            // This injects params into in-line sql, but we cast and clean all to make safe.
            $modulejoins[] = "SELECT id, name, $mid as module, $duedatecolumn as duedate
                                FROM {".$mname."}
                               WHERE course = $courseid";
        }
        $modulejoin = implode(' UNION ALL ', $modulejoins);
        $this->add_join("JOIN ({$modulejoin}) mmj ON mmj.id = {$cmalias}.instance AND mmj.module = {$cmalias}.module");

        $columns = [];

        // Module Icon column.
        $columns[] = (new column(
            'type',
            new lang_string('activity'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_is_sortable(true)
            ->add_field("{$modulesalias}.name")
            ->set_is_downloadable(false)
            ->add_callback(static function ($v): string {
                global $OUTPUT;
                return $OUTPUT->pix_icon('icon', $v, $v, array('class' => 'icon'));
            });

        // Module Icon column.
        $columns[] = (new column(
            'name',
            new lang_string('name'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_is_sortable(true)
            ->add_field("mmj.name");

        $columns[] = (new column(
            'namedashboardlink',
            new lang_string('activitynamedashboardlink', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("mmj.name as namedashboardlink, {$cmalias}.id")
            ->set_is_sortable(true)
            ->add_callback(static function(?string $value, stdClass $row): string {
                if ($value === null) {
                    return '';
                }
                $url = new moodle_url('/local/ace/goto.php', ['cmid' => $row->id]);
                return html_writer::link($url,
                    format_string($value, true));
            });

        // Date due.
        $columns[] = (new column(
            'duedate',
            new lang_string('due', 'local_ace'),
            $this->get_entity_name()
        ))
        ->add_joins($this->get_joins())
        ->set_is_sortable(true)
        ->set_type(column::TYPE_TIMESTAMP)
        ->add_fields("mmj.duedate")
        ->set_callback([format::class, 'userdate']);

        $viewcountsql = "LEFT JOIN (SELECT SUM(viewcount) AS viewcounttotal, cmid
                                      FROM {local_ace_log_summary}
	                                 WHERE courseid = $courseid
                                  GROUP BY cmid) {$totalviewcountalias}
                             ON {$totalviewcountalias}.cmid = {$cmalias}.id";

        $columns[] = (new column(
            'viewcounttotal',
            new lang_string('totalviews', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_join($viewcountsql)
            ->set_is_sortable(true)
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$totalviewcountalias}.viewcounttotal");

        $viewcountusersql = "LEFT JOIN (SELECT SUM(viewcount) AS viewcounttotal, cmid
                                      FROM {local_ace_log_summary}
	                                 WHERE courseid = $courseid AND userid = $userid
                                  GROUP BY cmid) {$totalviewcountuseralias}
                             ON {$totalviewcountuseralias}.cmid = {$cmalias}.id";
        $columns[] = (new column(
            'viewcounttotaluser',
            new lang_string('totalviewsuser', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_join($viewcountusersql)
            ->set_is_sortable(true)
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$totalviewcountuseralias}.viewcounttotal");

        $lastaccessanyjoin = "LEFT JOIN (SELECT contextinstanceid, MAX(timecreated) as lastaccessany
                                        FROM {logstore_standard_log}
                                       WHERE courseid = $courseid
                                    GROUP BY contextinstanceid) {$this->logstorealias1}
                                     ON {$this->logstorealias1}.contextinstanceid = {$cmalias}.id";

        $columns[] = (new column(
            'lastaccessanyuser',
            new lang_string('lastaccessanyuser', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join($lastaccessanyjoin)
            ->set_is_sortable(true)
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$this->logstorealias1}.lastaccessany")
            ->set_callback([format::class, 'userdate']);

        $lastaccessthisjoin = "LEFT JOIN (SELECT contextinstanceid, MAX(timecreated) as lastaccessthis
                                        FROM {logstore_standard_log}
                                       WHERE courseid = $courseid
                                             AND userid = $userid
                                    GROUP BY contextinstanceid) {$this->logstorealias2}
                                     ON {$this->logstorealias2}.contextinstanceid = {$cmalias}.id";

        $columns[] = (new column(
            'lastaccessthisuser',
            new lang_string('lastaccessthisuser', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join($lastaccessthisjoin)
            ->set_is_sortable(true)
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$this->logstorealias2}.lastaccessthis")
            ->set_callback([format::class, 'userdate']);

        $countallusersjoin = "JOIN (SELECT count(distinct userid) as countallusers, cmid
                                           FROM {local_ace_log_summary}
                                          WHERE courseid = $courseid
                                       GROUP BY cmid) {$this->logstorealias3}
                                        ON {$this->logstorealias3}.cmid = {$cmalias}.id";
        $columns[] = (new column(
            'countallusers',
            new lang_string('countallusers', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join($countallusersjoin)
            ->set_is_sortable(true)
            ->set_type(column::TYPE_INTEGER)
            ->add_field("{$this->logstorealias3}.countallusers");

        $studentroleid = (int)get_config('local_ace', 'studentrole');
        // This hard-codes against the student role which is usually not ideal, however we make a column above available
        // for sites that do not use wish to use the student role.
        $coursecontextid = empty($course) ? 0 : \context_course::instance($course->id)->id;
        $countallstudentsjoin = "JOIN (
                                    SELECT count(distinct userid) as countallusers, cmid
                                      FROM {local_ace_log_summary}
                                     WHERE courseid = $courseid AND userid IN (
                                           SELECT casju.id
                                             FROM {user} casju
                                             JOIN {role_assignments} casjra ON casjra.contextid = {$coursecontextid}
                                                  AND casjra.roleid = {$studentroleid} AND casjra.userid = casju.id)
                                  GROUP BY cmid) {$this->logstorealias4} ON {$this->logstorealias4}.cmid = {$cmalias}.id";

        $usercount = $this->get_usercount($courseid);
        $columns[] = (new column(
            'countallstudents',
            new lang_string('countallstudents', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join($countallstudentsjoin)
            ->set_is_sortable(true)
            ->set_type(column::TYPE_INTEGER)
            ->add_field("{$this->logstorealias4}.countallusers")
            ->add_callback(static function(?int $value) use ($usercount): string {
                if ($value === null) {
                    return '';
                }
                $percentage = empty($value) ? "0" : round(($value / $usercount) * 100);
                return $percentage . "% (".$value . '/'.$usercount.")";
            });

        $completionratejoin  = "LEFT JOIN (
                                    SELECT COUNT(DISTINCT userid) completionrate, cmid FROM (
                                        SELECT cmc.userid userid, cm.id cmid
                                        FROM {course_modules} cm
                                        JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id
                                        JOIN {user} u ON u.id = cmc.userid
                                        JOIN {role_assignments} ra ON ra.userid = u.id
                                        AND ra.contextid = $coursecontextid
                                        AND ra.roleid = $studentroleid
                                        WHERE cmc.completionstate > 0
                                        AND cm.course = $courseid
                                        AND u.deleted = 0
                                    ) AS completionrate GROUP BY cmid
                                ) {$this->logstorealias5} ON {$this->logstorealias5}.cmid = {$cmalias}.id";

        $columns[] = (new column(
            'completionrate',
            new lang_string('completionrate', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join($completionratejoin)
            ->set_is_sortable(true)
            ->set_type(column::TYPE_INTEGER)
            ->add_field("{$this->logstorealias5}.completionrate")
            ->add_callback(static function(?int $value) use ($usercount): string {
                if ($value === null) {
                    $value = 0;
                }
                $percentage = empty($value) ? "0" : round(($value / $usercount) * 100);
                return $percentage . "% (".$value . '/' . $usercount . ")";
            });

        $submissionratejoin  = "LEFT JOIN (
                                    SELECT COUNT(DISTINCT userid) submissionrate, cmid FROM (
                                        SELECT asub.userid userid, cm.id cmid
                                        FROM {assign} a
                                        JOIN {course_modules} cm ON a.id = cm.instance
                                        JOIN {modules} m ON m.id = cm.module AND m.name = 'assign'
                                        JOIN {assign_submission} asub ON asub.assignment = a.id
                                        JOIN {user} u ON u.id = asub.userid
                                        JOIN {role_assignments} ra ON ra.userid = u.id
                                        AND ra.contextid = $coursecontextid
                                        AND ra.roleid = $studentroleid
                                        WHERE asub.status = 'submitted'
                                        AND a.course = $courseid
                                        AND u.deleted = 0
                                    ) AS submissionrate GROUP BY cmid
                                ) {$this->logstorealias6} ON {$this->logstorealias6}.cmid = {$cmalias}.id";

        $columns[] = (new column(
            'submissionrate',
            new lang_string('submissionrate', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join($submissionratejoin)
            ->set_is_sortable(true)
            ->set_type(column::TYPE_INTEGER)
            ->add_field("{$this->logstorealias6}.submissionrate")
            ->add_callback(static function(?int $value) use ($usercount): string {
                if ($value === null) {
                    $value = 0;
                }
                $percentage = empty($value) ? "0" : round(($value / $usercount) * 100);
                return "$percentage% ($value/$usercount)";
            });

        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {

        $filters = [];
        $cmalias = $this->get_table_alias('course_modules');

        // Module name filter.
        $filters[] = (new filter(
            text::class,
            'nameselector',
            new lang_string('name'),
            $this->get_entity_name(),
            "mmj.name"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            coursemoduletype::class,
            'moduletype',
            new lang_string('moduletype', 'local_ace'),
            $this->get_entity_name(),
            "{$cmalias}.module"
        ))->add_joins($this->get_joins());

        $filters[] = (new filter(
            date::class,
            'lastaccessthis',
            new lang_string('lastaccessthisuser', 'local_ace'),
            $this->get_entity_name(),
            "{$this->logstorealias2}.lastaccessthis"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            date::class,
            'lastaccessany',
            new lang_string('lastaccessanyuser', 'local_ace'),
            $this->get_entity_name(),
            "{$this->logstorealias1}.lastaccessany"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            boolean_select::class,
            'visible',
            new lang_string('coursemodulevisible', 'local_ace'),
            $this->get_entity_name(),
            "{$cmalias}.visible"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            number::class,
            'completionrate',
            new lang_string('completionratefilter', 'local_ace'),
            $this->get_entity_name(),
            "{$this->logstorealias5}.completionrate"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            number::class,
            'submissionrate',
            new lang_string('submissionratefilter', 'local_ace'),
            $this->get_entity_name(),
            "{$this->logstorealias6}.submissionrate"
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }

    /**
     * Gets a count of the number of students in the course.
     *
     * @param int $course
     * @return int
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected function get_usercount($course): int {
        global $DB;
        $cache = \cache::make('local_ace', 'coursestudentcount');

        $usercount = $cache->get($course);
        if ($usercount !== false) {
            return $usercount;
        }
        $studentroleid = (int)get_config('local_ace', 'studentrole');

        $sql = "SELECT COUNT(DISTINCT u.id)
                  FROM {user} u
                  JOIN {role_assignments} ra ON ra.userid = u.id AND ra.roleid = :roleid
                  JOIN {context} cx ON cx.id = ra.contextid AND cx.contextlevel = " . CONTEXT_COURSE . "
                        AND cx.instanceid = :courseid
             WHERE u.deleted = 0";

        $count = $DB->count_records_sql($sql, ['courseid' => $course, 'roleid' => $studentroleid]);
        $cache->set($course, $count);
        return $count;
    }
}
