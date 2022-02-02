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
 * Library functions for local_ace.
 *
 * @package     local_ace
 * @copyright   2021 University of Canterbury
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Get the current user preferences that are available
 *
 * @return Array preferences configuration
 */
function local_ace_user_preferences() {
    return [
        'local_ace_teacher_hidden_courses' => [
            'type' => PARAM_TEXT,
            'null' => NULL_ALLOWED,
            'default' => 'none'
        ],
        'local_ace_comparison_method' => [
            'type' => PARAM_TEXT,
            'null' => NULL_ALLOWED,
            'default' => 'none'
        ],
        'local_ace_default_chart_filter' => [
            'type' => PARAM_TEXT,
            'null' => NULL_ALLOWED,
            'default' => 'none'
        ],
    ];
}

/**
 * Callback for rendering navbar.
 * If viewing a ACE(vxg) dashboard and have the view other users analytics capability the 'My courses' breadcrumb
 * is replaced with the teacher dashboard URL.
 *
 * @param renderer_base $renderer
 * @return void
 */
function local_ace_render_navbar_output(\renderer_base $renderer) {
    global $PAGE, $USER, $COURSE;

    if (strpos($PAGE->url->get_path(), '/local/vxg_dashboard/index.php') !== 0) {
        return;
    }

    if (!has_capability('local/ace:view', $PAGE->context, $USER)) {
        return;
    }

    $config = get_config('local_ace');

    // This catches the course & activity context dashboards for both enrolled and unenrolled users.
    foreach ($PAGE->navbar->get_items() as $item) {
        if ($item instanceof breadcrumb_navigation_node) {
            if ($item->key === 'mycourses' || $item->key === 'courses') {
                $item->text = get_string('myacedashboard', 'local_ace');
                $item->action = new moodle_url($config->teacherdashboardurl);
            }
            $courseurl = new moodle_url('/course/view.php', ['id' => $COURSE->id]);
            if (strpos($PAGE->url->out(), $config->coursemoduledashboardurl) === 0 && !empty($item->action) &&
                $item->action instanceof moodle_url && $item->action->compare($courseurl)) {
                $context = context_course::instance($COURSE->id);
                $item->text = $COURSE->shortname .' ' .get_string('acedashboard', 'local_ace');
                $item->action = new moodle_url($config->coursedashboardurl, ['contextid' => $context->id]);
            }
        }
    }
}

/**
 * Returns the list of all modules using a static var to prevent multiple db lookups.
 *
 * @return array where key is the module id and value is (component name without 'mod_')
 */
function local_ace_get_module_types() {
    static $modnames = null;
    global $DB, $CFG;
    if ($modnames === null) {
        $allmods = $DB->get_records("modules"); // TODO - cache this? find a better way?
        foreach ($allmods as $mod) {
            if (file_exists("$CFG->dirroot/mod/$mod->name/lib.php") && $mod->visible) {
                $modnames[$mod->id] = clean_param($mod->name, PARAM_ALPHANUMEXT);
            }
        }
    }
    return $modnames;
}

/**
 * This function extends the navigation with the report items
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param stdClass $context The context of the course
 */
function local_ace_extend_navigation_course($navigation, $course, $context) {
    $showonnavigation = has_capability('local/ace:view', $context);
    $coursedashboardurl = get_config('local_ace', 'coursedashboardurl');
    if ($showonnavigation && !empty($coursedashboardurl)) {
        $reportnode = $navigation->get('coursereports');
        $url = new moodle_url($coursedashboardurl, ['contextid' => $context->id]);
        $settingsnode = navigation_node::create(get_string('courseacedashboard', 'local_ace'), $url,
                    navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
        if (isset($settingsnode) && !empty($reportnode)) {
            $reportnode->add_node($settingsnode);
        }
    }
}

/**
 * Add nodes to myprofile page.
 *
 * @param \core_user\output\myprofile\tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $iscurrentuser
 * @param stdClass $course Course object
 *
 * @return bool
 */
function local_ace_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    if (isguestuser() or !isloggedin()) {
        return;
    }

    $context = context_user::instance($user->id);
    $userdashboardurl = get_config('local_ace', 'userdashboardurl');
    if (has_capability('local/ace:view', $context) && !empty($userdashboardurl)) {
        $url = new moodle_url($userdashboardurl, ['contextid' => $context->id]);
        $node = new core_user\output\myprofile\node('reports', 'ace',
            get_string('studentacedashboard', 'local_ace'), null,
            $url);
        $tree->add_node($node);
    }
    return true;
}

/**
 * Modify user dashboard breadcrumbs when a teacher is viewing a student dashboard.
 *
 * @return void
 * @throws coding_exception
 * @throws dml_exception
 * @throws moodle_exception
 */
function local_ace_before_http_headers() {
    global $PAGE;
    if (strpos($PAGE->url->get_path(), '/local/vxg_dashboard/index.php') !== 0) {
        return;
    }

    if (!has_capability('local/ace:view', $PAGE->context)) {
        return;
    }

    $config = get_config('local_ace');
    // If on the user dashboard page we add the teacher url to the second node.
    if (strpos($PAGE->url->out(), $config->userdashboardurl) === 0) {
        $PAGE->navbar->add(get_string('studentacedashboard', 'local_ace'));
        $dashboardnode = $PAGE->navbar->get_items()[1];
        $dashboardnode->text = get_string('myacedashboard', 'local_ace');
        $dashboardnode->action = new moodle_url($config->teacherdashboardurl);
    }
}
