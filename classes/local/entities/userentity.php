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

use context_system;
use html_writer;
use lang_string;
use moodle_url;
use stdClass;
use core_user\fields;
use core_reportbuilder\local\filters\boolean_select;
use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\filters\select;
use core_reportbuilder\local\filters\text;
use local_ace\local\filters\pagecontextcourse;
use local_ace\local\filters\myenrolledcourses;
use core_reportbuilder\local\helpers\user_profile_fields;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\report\base as base_report;

/**
 * User entity class implementation.
 *
 * This entity defines all the user columns and filters to be used in any report.
 *
 * @package    local_ace
 * @copyright  2021 University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class userentity extends base {

    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {
        return [
            'user' => 'uu',
            'enrol' => 'ue',
            'user_enrolments' => 'uue',
            'user_lastaccess' => 'uul',
            'course' => 'uc',
            'course_modules' => 'ucm',
            'modules' => 'um',
            'assign' => 'ua',
            'assign_submission' => 'uas',
            'logstore_standard_log' => 'ulsl',
            'context' => 'uctx',
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('userentitytitle', 'local_ace');
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

        $usertablealias = $this->get_table_alias('user');
        $userenrolmentsalias = $this->get_table_alias('user_enrolments');
        $coursealias = $this->get_table_alias('course');
        $coursemodulesalias = $this->get_table_alias('course_modules');
        $modulesalias = $this->get_table_alias('modules');
        $enrolalias = $this->get_table_alias('enrol');
        $assignalias = $this->get_table_alias('assign');
        $assignsubmissionalias = $this->get_table_alias('assign_submission');
        $logstorealias = $this->get_table_alias('logstore_standard_log');
        $userlastaccessalias = $this->get_table_alias('user_lastaccess');
        $contexttablealias = $this->get_table_alias('context');
        $logstorealiassub1 = 'logs_sub_select_1';
        $logstorealiassub2 = 'logs_sub_select_2';

        $join = "
                INNER JOIN {user_enrolments} {$userenrolmentsalias}
                ON {$userenrolmentsalias}.userid = {$usertablealias}.id
                INNER JOIN {enrol} {$enrolalias}
                ON {$enrolalias}.id = {$userenrolmentsalias}.enrolid
                INNER JOIN {course} {$coursealias}
                ON {$enrolalias}.courseid = {$coursealias}.id
                LEFT JOIN {context} {$contexttablealias}
                ON {$contexttablealias}.contextlevel = " . CONTEXT_COURSE . "
                AND {$contexttablealias}.instanceid = {$coursealias}.id
                LEFT JOIN (
                    SELECT contextid, max(timecreated) AS maxtimecreated, COUNT(*) AS last7
                    FROM {logstore_standard_log}
                    WHERE timecreated > extract(epoch from (now() - interval '7 days'))
                    GROUP BY contextid
                    ) AS {$logstorealiassub1} ON {$logstorealiassub1}.contextid = {$contexttablealias}.id
                    LEFT JOIN (
                    SELECT contextid, COUNT(*) AS last30
                    FROM {logstore_standard_log}
                    WHERE timecreated > extract(epoch from (now() - interval '30 days'))
                    GROUP BY contextid
                ) AS {$logstorealiassub2} ON {$logstorealiassub2}.contextid = {$contexttablealias}.id
        ";

        // Last access in 7 days column.
        $columns[] = (new column(
            'log7',
            new lang_string('last7', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_join($join)
            ->set_is_sortable(true)
            ->add_field("$logstorealiassub1.last7")
            ->add_callback(static function ($value): string {
                if (!$value) {
                    return '0';
                }
                return $value;
            });

        // Last access in 30 days column.
        $columns[] = (new column(
            'log30',
            new lang_string('last30', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_join($join)
            ->set_is_sortable(true)
            ->add_fields("$logstorealiassub2.last30")
            ->add_callback(static function ($value): string {
                if (!$value) {
                    return '0';
                }
                return $value;
            });

        return $columns;
    }

    /**
     * User fields
     *
     * @return lang_string[]
     */
    protected function get_user_fields(): array {
        return [
            'firstname' => new lang_string('firstname'),
            'lastname' => new lang_string('lastname'),
            'email' => new lang_string('email'),
            'city' => new lang_string('city'),
            'country' => new lang_string('country'),
            'firstnamephonetic' => new lang_string('firstnamephonetic'),
            'lastnamephonetic' => new lang_string('lastnamephonetic'),
            'middlename' => new lang_string('middlename'),
            'alternatename' => new lang_string('alternatename'),
            'idnumber' => new lang_string('idnumber'),
            'institution' => new lang_string('institution'),
            'department' => new lang_string('department'),
            'phone1' => new lang_string('phone1'),
            'phone2' => new lang_string('phone2'),
            'address' => new lang_string('address'),
            'lastaccess' => new lang_string('lastaccess'),
            'suspended' => new lang_string('suspended'),
            'confirmed' => new lang_string('confirmed', 'admin'),
            'username' => new lang_string('username'),
            'moodlenetprofile' => new lang_string('moodlenetprofile', 'user'),
        ];
    }

    /**
     * Return appropriate column type for given user field
     *
     * @param string $userfield
     * @return int
     */
    protected function get_user_field_type(string $userfield): int {
        switch ($userfield) {
            case 'confirmed':
            case 'suspended':
                $fieldtype = column::TYPE_BOOLEAN;
                break;
            case 'lastaccess':
                $fieldtype = column::TYPE_TIMESTAMP;
                break;
            default:
                $fieldtype = column::TYPE_TEXT;
                break;
        }

        return $fieldtype;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $filters = [];

        $tablealias = $this->get_table_alias('user');
        $coursetablealias = $this->get_table_alias('course');
        $userenrolmentsalias = $this->get_table_alias('user_enrolments');
        $coursealias = $this->get_table_alias('course');
        $coursemodulesalias = $this->get_table_alias('course_modules');
        $modulesalias = $this->get_table_alias('modules');
        $enrolalias = $this->get_table_alias('enrol');
        $assignalias = $this->get_table_alias('assign');
        $assignsubmissionalias = $this->get_table_alias('assign_submission');
        $logstorealias = $this->get_table_alias('logstore_standard_log');
        $userlastaccessalias = $this->get_table_alias('user_lastaccess');
        $contexttablealias = $this->get_table_alias('context');
        $logstorealiassub1 = 'logs_sub_select_1';
        $logstorealiassub2 = 'logs_sub_select_2';

        $join = "
                INNER JOIN {user_enrolments} {$userenrolmentsalias}
                ON {$userenrolmentsalias}.userid = {$tablealias}.id
                INNER JOIN {enrol} {$enrolalias}
                ON {$enrolalias}.id = {$userenrolmentsalias}.enrolid
                INNER JOIN {course} {$coursetablealias}
                ON {$enrolalias}.courseid = {$coursetablealias}.id
                LEFT JOIN {context} {$contexttablealias}
                ON {$contexttablealias}.contextlevel = " . CONTEXT_COURSE . "
                AND {$contexttablealias}.instanceid = {$coursealias}.id
                LEFT JOIN (
                    SELECT contextid, max(timecreated) AS maxtimecreated, COUNT(*) AS last7
                    FROM {logstore_standard_log}
                    WHERE timecreated > extract(epoch from (now() - interval '7 days'))
                    GROUP BY contextid
                    ) AS {$logstorealiassub1} ON {$logstorealiassub1}.contextid = {$contexttablealias}.id
                    LEFT JOIN (
                    SELECT contextid, COUNT(*) AS last30
                    FROM {logstore_standard_log}
                    WHERE timecreated > extract(epoch from (now() - interval '30 days'))
                    GROUP BY contextid
                ) AS {$logstorealiassub2} ON {$logstorealiassub2}.contextid = {$contexttablealias}.id
        ";

        $userenrolmentsalias = $this->get_table_alias('user_enrolments');
        $coursealias = $this->get_table_alias('course');
        $coursemodulesalias = $this->get_table_alias('course_modules');
        $modulesalias = $this->get_table_alias('modules');
        $enrolalias = $this->get_table_alias('enrol');
        $assignalias = $this->get_table_alias('assign');
        $assignsubmissionalias = $this->get_table_alias('assign_submission');
        $logstorealias = $this->get_table_alias('logstore_standard_log');
        $userlastaccessalias = $this->get_table_alias('user_lastaccess');
        $contexttablealias = $this->get_table_alias('context');
        $logstorealiassub1 = 'logs_sub_select_1';
        $logstorealiassub2 = 'logs_sub_select_2';

        $join = "
                INNER JOIN {user_enrolments} {$userenrolmentsalias}
                ON {$userenrolmentsalias}.userid = {$tablealias}.id
                INNER JOIN {enrol} {$enrolalias}
                ON {$enrolalias}.id = {$userenrolmentsalias}.enrolid
                INNER JOIN {course} {$coursetablealias}
                ON {$enrolalias}.courseid = {$coursetablealias}.id
                LEFT JOIN {context} {$contexttablealias}
                ON {$contexttablealias}.contextlevel = " . CONTEXT_COURSE . "
                AND {$contexttablealias}.instanceid = {$coursetablealias}.id
                LEFT JOIN (
                    SELECT contextid, max(timecreated) AS maxtimecreated, COUNT(*) AS last7
                    FROM {logstore_standard_log}
                    WHERE timecreated > extract(epoch from (now() - interval '7 days'))
                    GROUP BY contextid
                    ) AS {$logstorealiassub1} ON {$logstorealiassub1}.contextid = {$contexttablealias}.id
                    LEFT JOIN (
                    SELECT contextid, COUNT(*) AS last30
                    FROM {logstore_standard_log}
                    WHERE timecreated > extract(epoch from (now() - interval '30 days'))
                    GROUP BY contextid
                ) AS {$logstorealiassub2} ON {$logstorealiassub2}.contextid = {$contexttablealias}.id
        ";

        // Fullname filter.
        $canviewfullnames = has_capability('moodle/site:viewfullnames', context_system::instance());
        [$fullnamesql, $fullnameparams] = fields::get_sql_fullname($tablealias, $canviewfullnames);
        $filters[] = (new filter(
            text::class,
            'fullname',
            new lang_string('fullname'),
            $this->get_entity_name(),
            $fullnamesql,
            $fullnameparams
        ))
            ->add_joins($this->get_joins());

        // User fields filters.
        $fields = $this->get_user_fields();
        foreach ($fields as $field => $name) {
            $optionscallback = [static::class, 'get_options_for_' . $field];
            if (is_callable($optionscallback)) {
                $classname = select::class;
            } else if ($this->get_user_field_type($field) === column::TYPE_BOOLEAN) {
                $classname = boolean_select::class;
            } else if ($this->get_user_field_type($field) === column::TYPE_TIMESTAMP) {
                $classname = date::class;
            } else {
                $classname = text::class;
            }

            $filter = (new filter(
                $classname,
                $field,
                $name,
                $this->get_entity_name(),
                $tablealias . '.' . $field
            ))
                ->add_joins($this->get_joins());

            // Populate filter options by callback, if available.
            if (is_callable($optionscallback)) {
                $filter->set_options_callback($optionscallback);
            }

            $filters[] = $filter;
        }

        // End Time  filter.
        $filters[] = (new filter(
            text::class,
            'log7',
            new lang_string('last7', 'local_ace'),
            $this->get_entity_name(),
            "$logstorealiassub1.last7"
        ))
            ->add_join($join);

                // End Time  filter.
        $filters[] = (new filter(
            text::class,
            'last30',
            new lang_string('last30', 'local_ace'),
            $this->get_entity_name(),
            "$logstorealiassub2.last30"
        ))
            ->add_join($join);

        return $filters;
    }
}
