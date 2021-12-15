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
 * External functions for the activity analytics graph
 *
 * @package     local_ace
 * @copyright   2021 University of Canterbury
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/local/ace/locallib.php');

/**
 * Class activity_analytics_graph
 *
 * @package     local_ace
 * @copyright   2021 University of Canterbury
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_analytics_graph extends external_api {

    /**
     * Returns parameter types for get_activity_analytics_graph function.
     *
     * @return external_function_parameters
     */
    public static function get_activity_analytics_graph_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value(PARAM_INT, 'Course module ID'),
                'start' => new external_value(PARAM_INT, 'History start', false),
                'end' => new external_value(PARAM_INT, 'History end', false),
                'cumulative' => new external_value(PARAM_BOOL, 'Show cumulative', false, false),
            )
        );
    }

    /**
     * Get data required to create a chart for activity engagement.
     *
     * @param int $cmid Course module id
     * @param int|null $start Unix timestamp of start date
     * @param int|null $end Unix timestamp of end date
     * @param bool|null $cumulative
     * @return array
     * @throws coding_exception
     * @throws invalid_parameter_exception
     */
    public static function get_activity_analytics_graph(int $cmid, ?int $start, ?int $end, ?bool $cumulative = false): array {
        $params = self::validate_parameters(
            self::get_activity_analytics_graph_parameters(),
            array(
                'cmid' => $cmid,
                'start' => $start,
                'end' => $end,
                'cumulative' => $cumulative
            )
        );

        $context = context_module::instance($cmid);
        if (!has_capability('local/ace:view', $context)) {
            return array(
                'error' => get_string('nopermissions', 'error', get_capability_string('local/ace:view')),
                'series' => [],
                'xlabels' => [],
                'ylabels' => []
            );
        }

        $data = local_ace_course_module_engagement_data($cmid, $start, $end, $params['cumulative']);
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
     * Returns description of get_activity_analytics_graph() result values
     *
     * @return external_single_structure
     */
    public static function get_activity_analytics_graph_returns() {
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
            ),
            'max' => new external_value(PARAM_INT, 'Max value'),
            'stepsize' => new external_value(PARAM_INT, 'Stepsize'),
        ]);
    }

}
