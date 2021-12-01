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
use local_ace\local\entities\aceuser;
use local_ace\local\entities\coursemodules;
use local_ace\local\entities\userentity;
use local_ace\local\entities\acesamples;
use local_ace\local\entities\acecourse;
use local_ace\local\entities\userenrolment;
use core_reportbuilder\local\helpers\database;
use lang_string;
use moodle_url;

/**
 * Users datasource
 *
 * @package   local_ace
 * @copyright 2021 University of Canterbury
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class users extends datasource {

    /**
     * Return user friendly name of the datasource
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('users');
    }

    /**
     * Initialise report
     */
    protected function initialise(): void {
        // Enrolment entity.
        $enrolmententity = new userenrolment();
        $uetablealias = $enrolmententity->get_table_alias('user_enrolments');

        $this->set_main_table('user_enrolments', $uetablealias);
        $this->add_entity($enrolmententity);

        // Add core user join.
        $usercore = new aceuser();
        $usercorealias = $usercore->get_table_alias('user');
        $usercorejoin = "JOIN {user} {$usercorealias} ON {$usercorealias}.id = {$uetablealias}.userid";
        $this->add_entity($usercore->add_join($usercorejoin));

        // Add course entity.
        $courseentity = new acecourse();
        $coursetablealias = $courseentity->get_table_alias('course');
        $contexttablealias = 'cctxx';
        $coursejoin = "JOIN {enrol} exex1 ON exex1.id = $uetablealias.enrolid
                       JOIN {course} {$coursetablealias} ON {$coursetablealias}.id = exex1.courseid";

        $this->add_entity($courseentity->add_join($coursejoin));

        // Add the user entity for the last X days columns.
        $userentity = new userentity();
        $this->add_entity($userentity->add_join($coursejoin));

        // Add Ace samples entity.
        $acesamplesentity = new acesamples();
        $usertablealias = $userentity->get_table_alias('user');
        $acesamplesalias = $acesamplesentity->get_table_alias('local_ace_samples');
        $acesamplejoin = "LEFT JOIN {local_ace_samples} {$acesamplesalias}
                          ON {$acesamplesalias}.userid = {$usertablealias}.id
                          JOIN {context} {$contexttablealias}
                          ON {$contexttablealias}.id = {$acesamplesalias}.contextid";

        $this->add_entity($acesamplesentity->add_join($acesamplejoin));

        // Add course modules entity.
        $coursemodulesentity = new coursemodules();
        $coursemodulesalias = $coursemodulesentity->get_table_alias('course_modules');
        $coursemodulesjoin = "LEFT JOIN {course_modules} {$coursemodulesalias}
                          ON {$coursemodulesalias}.course = {$coursetablealias}.id";

        $this->add_entity($coursemodulesentity->add_join($coursemodulesjoin));

        $this->add_columns_from_entity($usercore->get_entity_name());
        $this->add_columns_from_entity($userentity->get_entity_name());
        $this->add_columns_from_entity($enrolmententity->get_entity_name());
        $this->add_columns_from_entity($courseentity->get_entity_name());
        $this->add_columns_from_entity($acesamplesentity->get_entity_name());
        $this->add_columns_from_entity($coursemodulesentity->get_entity_name());

        $this->add_filters_from_entity($usercore->get_entity_name());
        $this->add_filters_from_entity($userentity->get_entity_name());
        $this->add_filters_from_entity($enrolmententity->get_entity_name());
        $this->add_filters_from_entity($courseentity->get_entity_name());
        $this->add_filters_from_entity($acesamplesentity->get_entity_name());
        $this->add_filters_from_entity($coursemodulesentity->get_entity_name());

        $this->add_conditions_from_entity($usercore->get_entity_name());
        $this->add_conditions_from_entity($userentity->get_entity_name());
        $this->add_conditions_from_entity($enrolmententity->get_entity_name());
        $this->add_conditions_from_entity($courseentity->get_entity_name());
        $this->add_conditions_from_entity($acesamplesentity->get_entity_name());
        $this->add_conditions_from_entity($coursemodulesentity->get_entity_name());

        // Set default filter if not active - sets the default userenrolment filter to before 7days ago.
        $filtervalues = $this->get_filter_values();
        if (empty($filtervalues)) {
            $lastmidnight = usergetmidnight(time());
            $days7 = $lastmidnight - WEEKSECS;
            $defaultfilter['userenrolment:lastaccess_operator'] = 3;
            $defaultfilter['userenrolment:lastaccess_value'] = 1;
            $defaultfilter['userenrolment:lastaccess_unit'] = 1;
            $defaultfilter['userenrolment:lastaccess_to'] = $days7;
            $this->set_filter_values($defaultfilter);
        }

        $this->add_action_button([
            'id' => 'emailallselected',
            'value' => get_string('bulkemailallselected', 'local_ace'),
            'module' => 'local_ace/bulk_email'
        ]);
    }

    /**
     * Return the columns that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_columns(): array {
        return ['user:fullname', 'user:username', 'user:email'];
    }

    /**
     * Return the filters that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_filters(): array {
        return ['user:fullname', 'user:username', 'user:email'];
    }

    /**
     * Return the conditions that will be added to the report once is created
     *
     * @return string[]
     */
    public function get_default_conditions(): array {
        return ['aceuser:fullnamedashboardlink'];
    }
}
