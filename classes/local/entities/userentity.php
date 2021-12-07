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

use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use lang_string;

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
     * Three custom aliases for the logstore_standard_log table as we can't define multiple per entity using the API.
     * We need separate aliases for each in case the multiple columns are added to the same report.
     */
    /** @var string */
    private $logstorealias1 = "lsls1";
    /** @var string */
    private $logstorealias2 = "lsls2";
    /** @var string */
    private $logstorealias3 = "lsls3";

    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {
        return [
            'user' => 'u',
            'course' => 'c',
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
        $daysago7 = time() - (DAYSECS * 7);
        $daysago30 = time() - (DAYSECS * 30);

        $usertablealias = $this->get_table_alias('user');
        $coursealias = $this->get_table_alias('course');

        $join7days = "LEFT JOIN (
                           SELECT courseid, userid, COUNT(*) as last7
                               FROM {logstore_standard_log}
                           WHERE timecreated > $daysago7
                           GROUP BY courseid, userid) AS {$this->logstorealias1}
                       ON {$this->logstorealias1}.courseid = {$coursealias}.id
                       AND {$this->logstorealias1}.userid = {$usertablealias}.id";

        $join30days = "LEFT JOIN (
                           SELECT courseid, userid, COUNT(*) as last30
                               FROM {logstore_standard_log}
                           WHERE timecreated > $daysago30
                           GROUP BY courseid, userid) AS {$this->logstorealias2}
                       ON {$this->logstorealias2}.courseid = {$coursealias}.id
                       AND {$this->logstorealias2}.userid = {$usertablealias}.id";

        $jointotal = "LEFT JOIN (
                           SELECT courseid, userid, COUNT(*) as total
                               FROM {logstore_standard_log}
                           GROUP BY courseid, userid) AS {$this->logstorealias3}
                       ON {$this->logstorealias3}.courseid = {$coursealias}.id
                       AND {$this->logstorealias3}.userid = {$usertablealias}.id";

        $this->add_selectable_column('u');

        // Last access in 7 days column.
        $columns[] = (new column(
            'log7',
            new lang_string('last7', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join($join7days)
            ->set_is_sortable(true)
            ->add_field("{$this->logstorealias1}.last7")
            ->add_callback([$this, 'cleanup_log_Value']);

        // Last access in 30 days column.
        $columns[] = (new column(
            'log30',
            new lang_string('last30', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join($join30days)
            ->set_is_sortable(true)
            ->add_fields("{$this->logstorealias2}.last30")
            ->add_callback([$this, 'cleanup_log_Value']);

        // All accesses column.
        $columns[] = (new column(
            'logtotal',
            new lang_string('totalaccess', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join($jointotal)
            ->set_is_sortable(true)
            ->add_fields("{$this->logstorealias3}.total")
            ->add_callback([$this, 'cleanup_log_Value']);

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
     * Used to show zero values on access count columns.
     *
     * @param string|null $value
     * @return string
     */
    public function cleanup_log_value(?string $value): string {
        if (!$value) {
            return '0';
        }
        return $value;
    }
}
