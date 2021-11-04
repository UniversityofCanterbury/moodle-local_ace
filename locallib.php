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
 * ACE functions
 *
 * @package     local_ace
 * @copyright   2021 University of Canterbury
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Returns the HTML output for the teacher course engagement graph
 *
 * @param int $userid
 * @return string
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function local_ace_teacher_course_graph(int $userid): string {
    global $PAGE;

    $renderer = $PAGE->get_renderer('core');
    $output = $renderer->render_from_template('local_ace/teacher_course_engagement_chart', null);
    $PAGE->requires->js_call_amd('local_ace/teacher_course_engagement', 'init');
    $PAGE->requires->css('/local/ace/styles.css');
    return $output;
}

/**
 * Returns data required for teacher course data, from the users enrolled courses.
 *
 * @param int $userid
 * @param int|null $period
 * @param int|null $start
 * @param int|null $end
 * @return array|array[]
 * @throws coding_exception
 * @throws dml_exception
 */
function local_ace_teacher_course_data(int $userid, ?int $period = null, ?int $start = null, ?int $end = null): array {
    $data = array('series' => [], 'xlabels' => []);

    if ($period === null) {
        $period = (int) get_config('local_ace', 'displayperiod');
    }

    list($defaultcourseid, $courses) = local_ace_get_user_courses($userid);

    foreach ($courses as $course) {
        $values = local_ace_course_data_values($course->id, $period, $start, $end);
        $series = array();
        $laststart = null;
        $labels = empty($data['xlabels']);

        foreach ($values as $value) {
            if (!empty($laststart) && $value->endtime > $laststart) {
                // If this period overlaps with the last week, skip it in the display.
                continue;
            }

            if ($labels) {
                $data['xlabels'][] = userdate($value->endtime, get_string('strftimedate'));
            }
            if (empty($value->value)) {
                $series[] = 0;
            } else {
                $series[] = round(($value->value / $value->count) * 100); // Convert to average percentage.
            }
            // Make sure we don't show overlapping periods.
            $laststart = $value->starttime;
        }
        if (!empty($series)) {
            $data['series'][] = [
                'title' => $course->shortname,
                'values' => array_reverse($series)
            ];
        }
    }

    $data['xlabels'] = array_reverse($data['xlabels']);

    $data['ylabels'] = [
        [
            'value' => 0,
            'label' => get_string('none', 'local_ace')
        ],
        [
            'value' => 20,
            'label' => ''
        ],
        [
            'value' => 40,
            'label' => get_string('medium', 'local_ace')
        ],
        [
            'value' => 60,
            'label' => ''
        ],
        [
            'value' => 80,
            'label' => ''
        ],
        [
            'value' => 100,
            'label' => get_string('high', 'local_ace')
        ]
    ];

    return $data;
}

/**
 * Returns the course summary graph
 *
 * @param int $courseid
 * @return string
 * @throws dml_exception
 * @throws moodle_exception
 */
function local_ace_course_graph(int $courseid): string {
    global $PAGE;

    $config = get_config('local_ace');

    $context = array(
        'colourteachercoursehistory' => $config->colourteachercoursehistory,
        'courseid' => $courseid
    );

    $renderer = $PAGE->get_renderer('core');
    $output = $renderer->render_from_template('local_ace/course_engagement_chart', $context);
    $PAGE->requires->js_call_amd('local_ace/course_engagement', 'init', [$context]);
    $PAGE->requires->css('/local/ace/styles.css');
    return $output;
}

/**
 * Returns series data for course engagement data.
 *
 * @param int $courseid
 * @param int|null $period
 * @param int|null $start
 * @param int|null $end
 * @return array
 * @throws dml_exception
 */
function local_ace_course_data_values(int $courseid, ?int $period = null, ?int $start = null, ?int $end = null): array {
    global $DB;

    $config = get_config('local_ace');

    if ($period === null) {
        $period = (int) $config->displayperiod;
    }

    if ($start === null) {
        $start = time() - $config->userhistory;
    }

    $context = context_course::instance($courseid);

    $sql = "SELECT starttime, endtime, count(value) as count, sum(value) as value
              FROM {local_ace_contexts}
              WHERE contextid = :context AND (endtime - starttime = :period) AND endtime > :start
              " . ($end != null ? "AND endtime < :end " : "") . "
              GROUP BY starttime, endtime
              ORDER BY starttime DESC";

    $parameters = array(
        'context' => $context->id,
        'period' => $period,
        'start' => $start
    );
    if ($end != null) {
        $parameters['end'] = $end;
    }
    return $DB->get_records_sql($sql, $parameters);
}

/**
 * Get course summary graph data.
 *
 * @param int $courseid
 * @param int|null $period
 * @param int|null $start
 * @param int|null $end
 * @return array|string
 * @throws coding_exception
 * @throws dml_exception
 */
function local_ace_course_data(int $courseid, ?int $period = null, ?int $start = null, ?int $end = null) {
    $values = local_ace_course_data_values($courseid, $period, $start, $end);

    $labels = array();
    $series = array();
    $laststart = null;
    foreach ($values as $value) {
        if (!empty($laststart) && $value->endtime > $laststart) {
            // If this period overlaps with the last week, skip it in the display.
            continue;
        }
        $labels[] = userdate($value->endtime, get_string('strftimedate'));
        if (empty($value->value)) {
            $series[] = 0;
        } else {
            $series[] = round(($value->value / $value->count) * 100); // Convert to average percentage.
        }
        // Make sure we don't show overlapping periods.
        $laststart = $value->starttime;
    }

    if (empty($series)) {
        return get_string('noanalyticsfoundcourse', 'local_ace');
    }

    $ylabels = [
        [
            'value' => 0,
            'label' => get_string('none', 'local_ace')
        ],
        [
            'value' => 20,
            'label' => ''
        ],
        [
            'value' => 40,
            'label' => get_string('medium', 'local_ace')
        ],
        [
            'value' => 60,
            'label' => ''
        ],
        [
            'value' => 80,
            'label' => ''
        ],
        [
            'value' => 100,
            'label' => get_string('high', 'local_ace')
        ]
    ];

    return array(
        'series' => array_reverse($series),
        'xlabels' => array_reverse($labels),
        'ylabels' => $ylabels,
    );
}

/**
 * Returns the HTML output for the student engagement graph that includes a tab selector for courses.
 *
 * @param int $userid
 * @param int|null $courseid
 * @return string
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function local_ace_student_full_graph(int $userid, ?int $courseid = 0) {
    global $PAGE, $OUTPUT;

    list($courseid, $courses) = local_ace_get_user_courses($userid, $courseid);

    $config = get_config('local_ace');

    $tabs = array();

    foreach ($courses as $course) {
        $newurl = clone $PAGE->url;
        $newurl->param('course', $course->id);
        $tabs[] = new tabobject($course->id,
            $newurl,
            $course->shortname);
    }

    // Add overall tab last.
    if (count($courses) > 1) {
        $url = new moodle_url($PAGE->url);
        $url->param('course', 0);
        $tabs[] = new tabobject(0,
            $url,
            get_string('overallengagement', 'local_ace'));
    }

    $output = html_writer::start_div('useranalytics');

    $output .= print_tabs(array($tabs), $courseid, null, null, true);

    if (!empty($courseid)) {
        $output .= $OUTPUT->heading(format_string($courses[$courseid]->fullname), 3, 'coursename');
    }

    $context = array(
        'colourusercoursehistory' => $config->colourusercoursehistory,
        'colouruserhistory' => $config->colouruserhistory,
        'userid' => $userid,
    );

    $renderer = $PAGE->get_renderer('core');
    $output .= $renderer->render_from_template('local_ace/student_engagement_chart', $context);
    $PAGE->requires->js_call_amd('local_ace/student_engagement', 'init', [$context]);

    $output .= html_writer::end_div();

    return $output;
}

/**
 * Returns a list of courses and a course id that meet the following conditions:
 * - Contain analytics data
 * - The user is enrolled
 * - Not excluded
 *
 * @param int $userid
 * @param int|null $courseid
 * @return array array[0] = int (courseid) and array[1] = array (courses)
 * @throws coding_exception
 * @throws dml_exception
 */
function local_ace_get_user_courses(int $userid, ?int $courseid = 0): array {
    global $DB;

    $shortnameregs = get_config('local_ace', 'courseregex');
    $shortnamesql = '';
    if (!empty($shortnameregs)) {
        $shortnamesql = " AND co.shortname ~ '$shortnameregs' ";
    }
    $startfrom = time() - get_config('local_ace', 'userhistory');
    $period = get_config('local_ace', 'displayperiod');

    $sql = "SELECT DISTINCT co.id, co.shortname, co.enddate, co.fullname
              FROM {local_ace_samples} s
              JOIN {local_ace_contexts} c ON c.contextid = s.contextid
                   AND s.starttime = c.starttime AND s.endtime = c.endtime
              JOIN {context} cx ON c.contextid = cx.id AND cx.contextlevel = " . CONTEXT_COURSE . "
              JOIN {course} co ON cx.instanceid = co.id
              WHERE s.userid = :userid AND (s.endtime - s.starttime = :per) $shortnamesql
              AND s.endtime > :start ORDER BY co.shortname";

    $courses = $DB->get_records_sql($sql, array('userid' => $userid, 'per' => $period, 'start' => $startfrom));

    // TODO: Rename field to acecourseexclude, or define via setting.
    $excludefield = \core_customfield\field::get_record(array('shortname' => 'ucanalyticscourseexclude'));
    foreach ($courses as $course) {
        // Check enrollment.
        if (!is_enrolled(context_course::instance($course->id), $userid) ||
            empty($course->enddate) || $course->enddate < time()) {
            unset($courses[$course->id]);
        } else if (!empty($excludefield)) { // Check if this is an excluded course using the custom course field.
            $data = \core_customfield\data::get_record(array('instanceid' => $course->id, 'fieldid' => $excludefield->get('id')));
            if (!empty($data) && !empty($data->get("intvalue"))) {
                unset($courses[$course->id]);
            }
        }
    }

    if (count($courses) == 1 || ($courseid === null && !empty($courses))) {
        // Set courseid to the first course this user is enrolled in to make graph clear.
        $courseid = reset($courses)->id;
    }

    return array($courseid, $courses);
}

/**
 * Renders the chart based on given parameters.
 *
 * @param int $userid
 * @param int|array $courses
 * @param bool $showxtitles
 * @return bool|string
 * @throws coding_exception
 * @throws dml_exception
 */
function local_ace_student_graph(int $userid, $courses, bool $showxtitles = true) {
    global $OUTPUT;

    $config = get_config('local_ace');

    $data = local_ace_student_graph_data($userid, $courses, null, null, $showxtitles);
    if (empty($data['series'])) {
        return '';
    }

    $chart = new \core\chart_line();
    $chart->set_legend_options(['display' => false]);
    $chart->set_smooth(true);

    $chart->set_labels($data['labels']);

    $chartseries = new \core\chart_series(get_string('yourengagement', 'local_ace'), $data['series']);
    $chartseries->set_color($config->colouruserhistory);
    $chart->add_series($chartseries);

    // Check average course comparison data was included.
    if (count($data['comparison']) == 2) {
        if (empty($course)) {
            $averagelabel = get_string('averageengagement', 'local_ace');
        } else {
            $averagelabel = get_string('averagecourseengagement', 'local_ace');
        }
        $averageseries = new \core\chart_series($averagelabel, $data['comparison'][0]['values']);
        $averageseries->set_color($config->colourusercoursehistory);
        $chart->add_series($averageseries);

        $averageseries2 = new \core\chart_series($averagelabel, $data['comparison'][1]['values']);
        $averageseries2->set_color($config->colourusercoursehistory);
        $averageseries2->set_fill(1);
        $chart->add_series($averageseries2);
    }

    $yaxis0 = $chart->get_yaxis(0, true);
    $yaxis0->set_min(0);
    $yaxis0->set_max($data['max']);
    $yaxis0->set_stepsize($data['stepsize']);
    $yaxis0->set_labels(array(0 => get_string('low', 'local_ace'),
        $data['stepsize'] => get_string('medium', 'local_ace'),
        $data['max'] => get_string('high', 'local_ace')));

    return $OUTPUT->render($chart);
}

/**
 * Fetch graph data for specific user.
 *
 * @param int $userid
 * @param int|array $course
 * @param int|null $start Display period start, defaults to displaying all course history to date.
 * @param int|null $end Display period end
 * @param bool $showxtitles
 * @param string $comparison Comparison data source, defaults to average course engagement
 * @return array|string
 * @throws coding_exception
 * @throws dml_exception
 */
function local_ace_student_graph_data(int $userid, $course, ?int $start = null, ?int $end = null, ?bool $showxtitles = true,
    string $comparison = 'average-course-engagement') {
    global $DB;

    $config = get_config('local_ace');

    $period = (int) $config->displayperiod;

    $courseids = array();
    if (empty($course)) {
        // Get users enrolled courses, and use that instead.
        $courses = enrol_get_users_courses($userid, true, 'enddate');

        foreach ($courses as $course) {
            if (!empty($course->enddate) && $course->enddate > time()) {
                $courseids[] = $course->id;
            }
        }
    } else {
        $courseids = array($course);
    }
    if (empty($courseids)) {
        return get_string('noanalyticsfound', 'local_ace');
    }

    // Restrict to course passed, or enrolled users courses.
    list($insql, $inparamscf1) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'pa');
    $coursefilter = "AND co.id $insql";

    // Get this users stats.
    // Get Latest values first, so we always show the most recent data-set.
    $sql = "WITH samples AS (
                SELECT EXTRACT('epoch' FROM date_trunc('day', to_timestamp(starttime))) AS starttime,
                       EXTRACT('epoch' FROM date_trunc('day', to_timestamp(endtime))) AS endtime,
                       value,
                       userid
                FROM {local_ace_samples} s
                JOIN {context} cx ON s.contextid = cx.id AND cx.contextlevel = 50
                JOIN {course} co ON cx.instanceid = co.id
                WHERE (endtime - starttime = :per) "
        . ($start != null ? "AND endtime > :start" : "")
        . ($end != null ? "AND endtime < :end" : "")
        . " $coursefilter
            )
            SELECT s.starttime, s.endtime, count(s.value) AS count, sum(s.value) AS value, a.avg AS avg, a.stddev AS stddev
              FROM samples s
              JOIN (
                        SELECT starttime, endtime, stddev(value), avg(value)
                        FROM samples s
                        GROUP BY starttime, endtime
                    ) a ON a.starttime = s.starttime AND a.endtime = a.endtime
              WHERE s.userid = :userid
              GROUP BY s.starttime, s.endtime, avg, stddev
              ORDER BY s.starttime DESC";

    $params = $inparamscf1 + array('userid' => $userid, 'per' => $period, 'start' => $start);
    if ($start == null) {
        $params['start'] = time() - (int) $config->userhistory;
    }
    if ($end != null) {
        $params['end'] = $end;
    }

    $values = $DB->get_records_sql($sql, $params);

    $labels = array();
    $series = array();
    $average1 = array();
    $average2 = array();
    $laststart = null;

    foreach ($values as $value) {
        if (!empty($laststart) && $value->endtime > ($laststart + (DAYSECS))) {
            // If this period overlaps with the last week, skip it in the display.
            continue;
        }
        if ($showxtitles) {
            $labels[] = userdate($value->endtime, get_string('strftimedate'));
        } else {
            $labels[] = '';
        }

        if (empty($value->value)) {
            $series[] = 0;
        } else {
            $series[] = ($value->value / $value->count) * 100; // Convert to average percentage.
        }

        if (empty($value->avg)) {
            $average1[] = 0;
            $average2[] = 0;
        } else {
            $average1[] = ($value->avg - ($value->stddev / 2)) * 100;
            $average2[] = ($value->avg + ($value->stddev / 2)) * 100;
        }
        // Make sure we don't show overlapping periods.
        $laststart = $value->starttime;
    }

    if (empty($series)) {
        return get_string('noanalyticsfound', 'local_ace');
    }

    // Get max value to use as upper level of graph.
    $max = ceil(max(max($series), max($average1), max($average2)));

    // Charts.js doesn't cope when the stepsize is under 1.
    // Some of the courses have very little engagement so we occasionally end up with very low values.
    // This results in the Y axis having "high/high/high" instead of low/medium/high.
    // We do not want to show "real" values on the student graph, so the y-axis just autoscales to the max and low values.
    if ($max < 2) {
        $max = 2;
    }
    $stepsize = ceil($max / 2);

    switch ($comparison) {
        case 'average-course-engagement':
            $comparison = [
                [
                    'label' => 'Average course engagement',
                    'values' => array_reverse($average1),
                    'colour' => $config->colourusercoursehistory,
                ],
                [
                    'label' => 'Average course engagement',
                    'values' => array_reverse($average2),
                    'colour' => $config->colourusercoursehistory,
                    'fill' => true,
                ]
            ];
            break;
        default:
            $comparison = [];
    }

    // Reverse Series/labels to order by date correctly.
    return array(
        'series' => array_reverse($series),
        'labels' => array_reverse($labels),
        'comparison' => $comparison,
        'max' => $max,
        'stepsize' => $stepsize,
    );
}
