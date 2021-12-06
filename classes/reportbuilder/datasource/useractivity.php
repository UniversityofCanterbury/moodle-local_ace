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

namespace local_ace\reportbuilder\datasource;

use core_reportbuilder\datasource;
use core_reportbuilder\local\entities\user;
use local_ace\local\entities\activityengagement;

/**
 * Users from course within activity context
 *
 * @package     local_ace
 * @copyright   2021 University of Canterbury
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class useractivity extends datasource {

    /**
     * Return user friendly name of the datasource
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('useractivity', 'local_ace');
    }

    /**
     * Initialise report
     */
    protected function initialise(): void {
        $activityengagement = new activityengagement();
        $userentity = new user();

        $cmalias = $activityengagement->get_table_alias('course_modules');
        $useralias = $userentity->get_table_alias('user');
        $coursealias = $activityengagement->get_table_alias('course');
        $enrolalias = $activityengagement->get_table_alias('enrol');
        $uealias = $activityengagement->get_table_alias('user_enrolments');
        $contextalias = $activityengagement->get_table_alias('context');

        $userjoin = "JOIN {course} {$coursealias} ON {$coursealias}.id = {$cmalias}.course
                    JOIN {enrol} {$enrolalias} ON {$enrolalias}.courseid = {$coursealias}.id
                    JOIN {user_enrolments} {$uealias} ON {$uealias}.enrolid = {$enrolalias}.id
                    JOIN {user} {$useralias} ON {$useralias}.id = {$uealias}.userid
                    JOIN {context} {$contextalias} ON {$contextalias}.instanceid = {$cmalias}.id
                    AND {$contextalias}.contextlevel = " . CONTEXT_MODULE;

        $this->set_main_table('course_modules', $cmalias);
        $this->add_entity($activityengagement->add_join($userjoin));

        $this->add_entity($userentity->add_join($userjoin));

        $this->add_columns_from_entity($activityengagement->get_entity_name());
        $this->add_columns_from_entity($userentity->get_entity_name());

        $this->add_filters_from_entity($activityengagement->get_entity_name());
        $this->add_filters_from_entity($userentity->get_entity_name());

        $this->add_conditions_from_entity($activityengagement->get_entity_name());
        $this->add_conditions_from_entity($userentity->get_entity_name());
    }

    /**
     * Return the columns that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_columns(): array {
        return [];
    }

    /**
     * Return the filters that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_filters(): array {
        return [];
    }

    /**
     * Return the conditions that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_conditions(): array {
        return [];
    }

}
