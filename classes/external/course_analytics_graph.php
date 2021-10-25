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
 * External functions for the course analytics graph
 *
 * @package     local_ace
 * @copyright   2021 University of Canterbury
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/local/ace/locallib.php');

/**
 * Class course_analytics_graph
 *
 * @package     local_ace
 * @copyright   2021 University of Canterbury
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_analytics_graph extends external_api {

    /**
     * Returns parameter types for get_course_analytics_graph function.
     *
     * @return external_function_parameters
     */
    public static function get_course_analytics_graph_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'Course id'),
                'period' => new external_value(PARAM_INT, 'History period', false),
                'start' => new external_value(PARAM_INT, 'History start', false),
                'end' => new external_value(PARAM_INT, 'History end', false)
            )
        );
    }

    /**
     * Get data required to create a chart for course engagement/analytics.
     *
     * @param int $courseid Course id
     * @param int|null $period Display period
     * @param int|null $start Unix timestamp of start date
     * @param int|null $end Unix timestamp of end date
     * @return array|string
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function get_course_analytics_graph($courseid, $period, $start, $end) {
        self::validate_parameters(
            self::get_course_analytics_graph_parameters(),
            array(
                'courseid' => $courseid,
                'period' => $period,
                'start' => $start,
                'end' => $end
            )
        );

        $coursecontext = context_course::instance($courseid);
        if (!has_capability('local/ace:view', $coursecontext)) {
            return array(
                'error' => get_string('nopermissions', 'error', get_capability_string('local/ace:view')),
                'series' => [],
                'xlabels' => [],
                'ylabels' => []
            );
        }

        $data = local_ace_course_data($courseid, $period, $start, $end);
        if (!is_array($data)) {
            return array(
                'error' => $data,
                'series' => [],
                'xlabels' => [],
                'ylabels' => []
            );
        }

        return $data;
    }

    /**
     * Returns description of get_course_analytics_graph() result values
     *
     * @return external_single_structure
     */
    public static function get_course_analytics_graph_returns() {
        return new external_single_structure([
            'error' => new external_value(PARAM_TEXT, 'Lang string of error, empty if working', false),
            'series' => new external_multiple_structure(
                new external_value(PARAM_FLOAT, 'Series value')
            ),
            'xlabels' => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'Formatted date string labels')
            ),
            'ylabels' => new external_multiple_structure(
                new external_single_structure([
                    'value' => new external_value(PARAM_FLOAT, 'Engagement Value'),
                    'label' => new external_value(PARAM_TEXT, 'Engagement Label')
                ])
            )
        ]);
    }

}
