<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * External functions for the user analytics graph
 *
 * @package     local_ace
 * @copyright   2021 University of Canterbury
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/local/ace/locallib.php');

/**
 * Class user_analytics_graph
 *
 * @package     local_ace
 * @copyright   2021 University of Canterbury
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_analytics_graph extends external_api {

    /**
     * Returns parameter types for get_user_analytics_graph function.
     *
     * @return external_function_parameters
     */
    public static function get_user_analytics_graph_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'ID of user', false),
                'courseid' => new external_value(PARAM_INT, 'Course id', false),
                'start' => new external_value(PARAM_INT, 'History start', false),
                'end' => new external_value(PARAM_INT, 'History end', false),
                'comparison' => new external_value(PARAM_TEXT, 'Course comparison data', false, 'average-course-engagement'),
                'showallcourses' => new external_value(PARAM_BOOL, 'Retrieve all enrolled courses', false, false),
            )
        );
    }

    /**
     * Get data required to create a chart for user engagement/analytics.
     *
     * @param int|null $userid
     * @param int|null $courseid
     * @param int|null $start Unix timestamp of start date
     * @param int|null $end Unix timestamp of end date
     * @param string|null $comparison Course comparison data
     * @param bool $showallcourses Changes the results to return data for all enrolled in courses.
     * @return array|string
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function get_user_analytics_graph(?int $userid, ?int $courseid, ?int $start, ?int $end, ?string $comparison,
        bool $showallcourses = false) {
        global $USER, $DB;

        $params = self::validate_parameters(
            self::get_user_analytics_graph_parameters(),
            array(
                'userid' => $userid,
                'courseid' => $courseid,
                'start' => $start,
                'end' => $end,
                'comparison' => $comparison,
                'showallcourses' => $showallcourses
            )
        );

        if ($params['userid'] == null) {
            $params['userid'] = $USER->id;
        }

        $supplieduser = $DB->get_record('user', array('id' => $params['userid'], 'deleted' => 0), '*', MUST_EXIST);
        $personalcontext = context_user::instance($supplieduser->id);
        if ($params['userid'] == $USER->id && !has_capability('local/ace:viewown', $personalcontext)) {
            return array(
                'error' => get_string('nopermissions', 'error', get_capability_string('local/ace:viewown')),
            );
        } else if ($params['userid'] != $USER->id && !has_capability('local/ace:view', $personalcontext)) {
            return array(
                'error' => get_string('nopermissions', 'error', get_capability_string('local/ace:view')),
            );
        }

        list($courseid, $courses) = local_ace_get_user_courses($params['userid'], $params['courseid']);
        // Get data for all courses user is enrolled in.
        if ($showallcourses) {
            $data = local_ace_enrolled_courses_user_data($params['userid'], null, $params['start'], $params['end']);
            if (empty($data['series'])) {
                return array(
                    'error' => get_string('noanalytics', 'local_ace')
                );
            }
            $data['data'] = $data['series'];
            unset($data['series']);
        } else {
            $data = local_ace_student_graph_data($params['userid'], $courseid, $params['start'], $params['end'], true,
                $params['comparison']);
        }
        if (!is_array($data)) {
            return array(
                'error' => $data,
            );
        }

        return $data;
    }

    /**
     * Returns description of get_user_analytics_graph() result values
     *
     * @return external_single_structure
     */
    public static function get_user_analytics_graph_returns() {
        return new external_single_structure([
            'error' => new external_value(PARAM_TEXT, 'Lang string of error, empty if working', false),
            'data' => new external_multiple_structure(
                new external_single_structure([
                    'values' => new external_multiple_structure(new external_value(PARAM_FLOAT, 'Series value')),
                    'label' => new external_value(PARAM_TEXT, 'Series label'),
                    'colour' => new external_value(PARAM_TEXT, 'Hex cour of series', false),
                    'fill' => new external_value(PARAM_BOOL, 'Fill between series', false, false)
                ]),
                'Series data', false
            ),
            'xlabels' => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'Formatted date string label'), 'X axis labels', false
            ),
            'ylabels' => new external_multiple_structure(
                new external_single_structure([
                    'value' => new external_value(PARAM_FLOAT, 'Engagement Value'),
                    'label' => new external_value(PARAM_TEXT, 'Engagement Label')
                ]),
                'Y axis labels',
                false
            )
        ]);
    }

}
