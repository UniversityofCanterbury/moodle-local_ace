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
 * Web service definitions
 *
 * @package     local_ace
 * @copyright   2021 University of Canterbury
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'local_ace_get_user_analytics_graph' => array(
        'classname' => 'user_analytics_graph',
        'methodname' => 'get_user_analytics_graph',
        'classpath' => 'local/ace/classes/external/user_analytics_graph.php',
        'description' => 'Get the analytics graph for a specific user.',
        'type' => 'read',
        'capabilities' => '', // Capabilities depend on content user is fetching, checked in webservice method.
        'ajax' => true,
        'services' => array('local_ace_webservice'),
    ),
    'local_ace_get_course_analytics_graph' => array(
        'classname' => 'course_analytics_graph',
        'methodname' => 'get_course_analytics_graph',
        'classpath' => 'local/ace/classes/external/course_analytics_graph.php',
        'description' => 'Get historic course analytics',
        'type' => 'read',
        'capabilities' => '',
        'ajax' => true,
        'services' => array('local_ace_webservice')
    ),
    'local_ace_get_teacher_course_analytics_graph' => array(
        'classname' => 'teacher_course_analytics_graph',
        'methodname' => 'get_teacher_course_analytics_graph',
        'classpath' => 'local/ace/classes/external/teacher_course_analytics_graph.php',
        'description' => 'Get teacher course analytics',
        'type' => 'read',
        'capabilities' => '',
        'ajax' => true,
        'services' => array('local_ace_webservice')
    ),
    'local_ace_get_activity_analytics_graph' => array(
        'classname' => 'activity_analytics_graph',
        'methodname' => 'get_activity_analytics_graph',
        'classpath' => 'local/ace/classes/external/activity_analytics_graph.php',
        'description' => 'Get activity analytics data',
        'type' => 'read',
        'capabilities' => '',
        'ajax' => true,
        'services' => array('local_ace_webservice')
    ),
    'local_ace_send_bulk_emails' => array(
        'classname' => 'bulk_emails',
        'methodname' => 'send_bulk_emails',
        'classpath' => 'local/ace/classes/external/bulk_emails.php',
        'description' => 'Send bulk emails',
        'type' => 'write',
        'capabilities' => 'local/ace:sendbulkemails',
        'ajax' => true,
        'services' => array('local_ace_webservice')
    ),
    'local_ace_send_bulk_emails_all' => array(
        'classname' => 'bulk_emails_all',
        'methodname' => 'send_bulk_emails_all',
        'classpath' => 'local/ace/classes/external/bulk_emails_all.php',
        'description' => 'Send bulk emails all',
        'type' => 'write',
        'capabilities' => 'local/ace:sendbulkemails',
        'ajax' => true,
        'services' => array('local_ace_webservice')
    ),
);

$services = array(
    'ACE Webservice' => array(
        'functions' => array(),
        'enabled' => 1,
        'restrictedusers' => 0,
        'shortname' => 'local_ace_webservice',
    ),
);
