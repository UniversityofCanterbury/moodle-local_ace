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

use context_course;
use context_system;
use context_helper;
use core_course_category;
use core_reportbuilder\local\filters\boolean_select;
use core_reportbuilder\local\filters\course_selector;
use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\filters\select;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\helpers\custom_fields;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use core_reportbuilder\local\entities\base;
use core_user\fields;
use core_reportbuilder\local\helpers\user_profile_fields;
use core_reportbuilder\local\entities\user;
use html_writer;
use lang_string;
use stdClass;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Course entity class implementation
 *
 * This entity defines all the course columns and filters to be used in any report.
 *
 * @package     local_ace
 * @copyright   2021 University of Canterbury
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activityentity extends base {

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

        $userentity = new user();
        $usertablealias = $userentity->get_table_alias('user');

        $columns = $this->get_all_columns();
        foreach ($columns as $column) {
            $this->add_column($column);
        }

        $filters = $this->get_all_filters();
        foreach ($filters as $filter) {
            $this->add_filter($filter);
        }

        // TODO: differentiate between filters and conditions (specifically the 'date' type: MDL-72662).
        $conditions = $this->get_all_filters();
        foreach ($conditions as $condition) {
            $this->add_condition($condition);
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

        $columns = [];
        $usertablealias = $this->get_table_alias('user');
        $userenrolmentsalias = $this->get_table_alias('user_enrolments');
        $coursealias = $this->get_table_alias('course');
        $coursemodulesalias = $this->get_table_alias('course_modules');
        $modulesalias = $this->get_table_alias('modules');
        $enrolalias = $this->get_table_alias('enrol');
        $assignalias = $this->get_table_alias('assign');
        $assignsubmissionalias = $this->get_table_alias('assign_submission');
        $logstorealias = $this->get_table_alias('logstore_standard_log');
        $contexttablealias = $this->get_table_alias('context');

        $join = "
                    INNER JOIN {user_enrolments} {$userenrolmentsalias}
                    ON {$userenrolmentsalias}.userid = {$usertablealias}.id
                    INNER JOIN {enrol} {$enrolalias}
                    ON {$enrolalias}.id = {$userenrolmentsalias}.enrolid
                    INNER JOIN {course} {$coursealias}
                    ON {$enrolalias}.courseid = {$coursealias}.id
                    INNER JOIN {course_modules} {$coursemodulesalias}
                    ON {$coursemodulesalias}.course = {$coursealias}.id
                    LEFT JOIN {assign} {$assignalias}
                    ON {$coursemodulesalias}.instance = {$assignalias}.id
                    INNER JOIN {assign_submission} {$assignsubmissionalias}
                    ON {$assignalias}.id = {$assignsubmissionalias}.assignment
                    INNER JOIN {modules} {$modulesalias}
                    ON {$coursemodulesalias}.module = {$modulesalias}.id
                    LEFT JOIN {context} {$contexttablealias}
                    ON {$contexttablealias}.contextlevel = " . CONTEXT_MODULE . "
                    AND {$contexttablealias}.instanceid = {$coursemodulesalias}.instance
                    LEFT JOIN (
                        SELECT contextid, max(timecreated) AS timecreated, COUNT(*) AS numberofaccess
                        FROM {logstore_standard_log}
                        GROUP BY contextid
                    ) AS {$logstorealias} ON {$logstorealias}.contextid = {$contexttablealias}.id
                ";

        // Module name column.
        $columns[] = (new column(
            'name',
            new lang_string('name'),
            $this->get_entity_name()
        ))
            ->add_join($join)
            ->set_is_sortable(true)
            ->add_field("{$modulesalias}.name")
            ->add_callback(static function ($v): string {
                return html_writer::img('/mod/'.$v.'/pix/icon.png', new lang_string('logo', 'local_ace')).' '.$v;
            });

        // Last accessed.
        $columns[] = (new column(
            'lastaccessed',
            new lang_string('lastaccessed', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_join($join)
            ->set_is_sortable(true)
            ->add_fields("{$logstorealias}.timecreated, {$coursemodulesalias}.id")
            ->add_callback(static function ($value, $row): string {
                return userdate($value);
            });

        // Last accessed.
        $columns[] = (new column(
            'numberofaccess',
            new lang_string('numofaccess', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_join($join)
            ->set_is_sortable(true)
            ->add_fields("{$logstorealias}.numberofaccess")
            ->add_callback(static function ($value): string {
                if (!$value) {
                    return '0';
                }
                return $value;
            });

        // Date due.
        $columns[] = (new column(
            'due',
            new lang_string('due', 'local_ace'),
            $this->get_entity_name()
        ))
        ->add_join($join)
        ->set_is_sortable(true)
        ->add_field("{$modulesalias}.name")
        ->add_fields("{$assignalias}.duedate")
        ->add_callback(static function ($value, $row): string {
            if ($row->name == 'assign') {
                $now = time();
                $date = $row->duedate;
                $datediff = $date - $now;
                $duein = round($datediff / (60 * 60 * 24));

                if ($duein <= 7) {
                    $html = html_writer::start_span('fa fa-calendar-o', array('style' =>
                                                                              "font-size: 25px;
                                                                               position: absolute"));
                    $html .= html_writer::start_span('duein', array('style' =>
                                                                    "font-size: 14px;
                                                                     position: relative;
                                                                     left: -16px;"));
                    $html .= $duein;
                    $html .= html_writer::end_span();
                    $html .= html_writer::end_span();
                    $html .= html_writer::start_span('duedate', array('style' => "margin-left: 40px;"));
                    $html .= ' ' . userdate($row->duedate);
                    $html .= html_writer::end_span();
                    return (string)$html;
                } else if ($duein <= 0) {
                    return html_writer::start_span('', array('style' => "color: red;")) .
                                        userdate($row->duedate) . html_writer::end_span();
                }
                return userdate($row->duedate);
            } else {
                return 'N/A';
            }
        });

        // Date submitted.
        $columns[] = (new column(
            'submitted',
            new lang_string('submitted', 'local_ace'),
            $this->get_entity_name()
        ))
        ->add_join($join)
        ->set_is_sortable(true)
        ->add_fields("{$assignsubmissionalias}.status, {$assignalias}.duedate")
        ->add_callback(static function ($value, $row): string {
            if ($row->status == 'submitted') {
                return ucfirst($value);
            } else {
                    $now = time();
                    $date = $row->duedate;
                    $datediff = $date - $now;
                    $duein = round($datediff / (60 * 60 * 24));
                if ($duein <= 0) {
                    return html_writer::start_span('submitted', array('style' => "color: red;")) .
                                                   'Not Submitted' . html_writer::end_span();
                }
                return html_writer::start_span('submitted') . 'Not Submitted' . html_writer::end_span();
            }
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

        $usertablealias = $this->get_table_alias('user');
        $userenrolmentsalias = $this->get_table_alias('user_enrolments');
        $coursealias = $this->get_table_alias('course');
        $coursemodulesalias = $this->get_table_alias('course_modules');
        $modulesalias = $this->get_table_alias('modules');
        $enrolalias = $this->get_table_alias('enrol');
        $assignalias = $this->get_table_alias('assign');
        $assignsubmissionalias = $this->get_table_alias('assign_submission');
        $logstorealias = $this->get_table_alias('logstore_standard_log');
        $contexttablealias = $this->get_table_alias('context');

        $join = "
                    INNER JOIN {user_enrolments} {$userenrolmentsalias}
                    ON {$userenrolmentsalias}.userid = {$usertablealias}.id
                    INNER JOIN {enrol} {$enrolalias}
                    ON {$enrolalias}.id = {$userenrolmentsalias}.enrolid
                    INNER JOIN {course} {$coursealias}
                    ON {$enrolalias}.courseid = {$coursealias}.id
                    INNER JOIN {course_modules} {$coursemodulesalias}
                    ON {$coursemodulesalias}.course = {$coursealias}.id
                    LEFT JOIN {assign} {$assignalias}
                    ON {$coursemodulesalias}.instance = {$assignalias}.id
                    INNER JOIN {assign_submission} {$assignsubmissionalias}
                    ON {$assignalias}.id = {$assignsubmissionalias}.assignment
                    INNER JOIN {modules} {$modulesalias}
                    ON {$coursemodulesalias}.module = {$modulesalias}.id
                    LEFT JOIN {context} {$contexttablealias}
                    ON {$contexttablealias}.contextlevel = " . CONTEXT_MODULE . "
                    AND {$contexttablealias}.instanceid = {$coursemodulesalias}.instance
                    LEFT JOIN (
                        SELECT contextid, max(timecreated) AS timecreated, COUNT(*) AS numberofaccess
                        FROM {logstore_standard_log}
                        GROUP BY contextid
                    ) AS {$logstorealias} ON {$logstorealias}.contextid = {$contexttablealias}.id
                ";

        // Module name filter.
        $filters[] = (new filter(
            text::class,
            'nameselector',
            new lang_string('name'),
            $this->get_entity_name(),
            "{$modulesalias}.name"
        ))
            ->add_join($join);

        // Last accessed filter.
        $filters[] = (new filter(
            date::class,
            'lastaccessedselector',
            new lang_string('lastaccessed', 'local_ace'),
            $this->get_entity_name(),
            "{$logstorealias}.timecreated"
        ))
            ->add_join($join);

        // Due date filter.
        $filters[] = (new filter(
            date::class,
            'dueselector',
            new lang_string('due', 'local_ace'),
            $this->get_entity_name(),
            "{$assignalias}.duedate"
        ))
            ->add_join($join);

        // Date submitted filter.
        $filters[] = (new filter(
            date::class,
            'submittedselector',
            new lang_string('submitted', 'local_ace'),
            $this->get_entity_name(),
            "{$assignsubmissionalias}.status"
        ))
            ->add_join($join);

        return $filters;
    }
}
