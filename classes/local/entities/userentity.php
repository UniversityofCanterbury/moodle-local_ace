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

use lang_string;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use core_reportbuilder\local\entities\base;

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
            'user' => 'aceu',
            'enrol' => 'aceue',
            'user_enrolments' => 'aceuue',
            'user_lastaccess' => 'aceuul',
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
        $enrolalias = $this->get_table_alias('enrol');
        $contexttablealias = $this->get_table_alias('context');
        $logstorealiassub1 = 'logs_sub_select_1';
        $logstorealiassub2 = 'logs_sub_select_2';

        $daysago7 = time() - (DAYSECS * 7);
        $daysago30 = time() - (DAYSECS * 30);

        // TODO: This is not a very clean join - we should tidy it up and split it.
        $join = "JOIN {user_enrolments} {$userenrolmentsalias} ON {$userenrolmentsalias}.userid = {$usertablealias}.id
                 JOIN {enrol} {$enrolalias} ON {$enrolalias}.id = {$userenrolmentsalias}.enrolid
                 JOIN {course} {$coursealias} ON {$enrolalias}.courseid = {$coursealias}.id
                 JOIN {context} {$contexttablealias} ON {$contexttablealias}.contextlevel = " . CONTEXT_COURSE . "
                      AND {$contexttablealias}.instanceid = {$coursealias}.id";
        $this->add_join($join);

        $join7day = "LEFT JOIN (
                    SELECT contextid, userid, max(timecreated) AS maxtimecreated, COUNT(*) AS last7
                      FROM {logstore_standard_log}
                     WHERE timecreated > $daysago7
                  GROUP BY contextid, userid) AS {$logstorealiassub1}
                   ON {$logstorealiassub1}.contextid = {$contexttablealias}.id
                   AND {$logstorealiassub1}.userid = {$usertablealias}.id";

        $join30day = "LEFT JOIN (
                    SELECT contextid, COUNT(*) AS last30
                      FROM {logstore_standard_log}
                     WHERE timecreated > $daysago30
                  GROUP BY contextid, userid) AS {$logstorealiassub2}
                   ON {$logstorealiassub2}.contextid = {$contexttablealias}.id
                   AND {$logstorealiassub1}.userid = {$usertablealias}.id";

        $this->add_selectable_column('u');

        // Last access in 7 days column.
        $columns[] = (new column(
            'log7',
            new lang_string('last7', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join($join7day)
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
            ->add_joins($this->get_joins())
            ->add_join($join30day)
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
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $filters = [];

        $enrolalias = $this->get_table_alias('enrol');
        $logstorealiassub1 = 'logs_sub_select_1';
        $logstorealiassub2 = 'logs_sub_select_2';

        // End Time  filter.
        $filters[] = (new filter(
            text::class,
            'log7',
            new lang_string('last7', 'local_ace'),
            $this->get_entity_name(),
            "$logstorealiassub1.last7"
        ))
            ->add_joins($this->get_joins());

                // End Time  filter.
        $filters[] = (new filter(
            text::class,
            'last30',
            new lang_string('last30', 'local_ace'),
            $this->get_entity_name(),
            "$logstorealiassub2.last30"
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }
}
