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
use core_reportbuilder\local\helpers\format;
use local_ace\local\filters\acedate;
use local_ace\local\filters\pagecontextcourse;
use local_ace\local\filters\myenrolledcourses;

/**
 * Userenrolment entity class implementation.
 *
 * @package    local_ace
 * @copyright  2021 University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class userenrolment extends base {

    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {
        return [
            'enrol' => 'uee',
            'user_enrolments' => 'ueue',
            'role' => 'uer',
            'user_lastaccess' => 'ueul',
            'context' => 'uectx',
            'role_assignments' => 'eura',
            'groups' => 'uegr',
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('entityenrolment', 'local_ace');
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

        // All the filters defined by the entity can also be used as conditions.
        $filters = $this->get_all_filters();
        foreach ($filters as $filter) {
            $this
                ->add_filter($filter)
                ->add_condition($filter);
        }

        return $this;
    }

    /**
     * Returns list of all available columns.
     *
     * Time enrolment started (user_enrolments.timestart)
     * Time enrolment ended (user_enrolments.timeend)
     * Time created (user_enrolments.timeend),
     * Enrol plugin used (mdl_enrol.enrol)
     * Role given to user (mdl_role_assignments.roleid - allowing for role shortname.
     * User last access (join with mdl_user_lastaccess table)
     *
     * These are all the columns available to use in any report that uses this entity.
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        global $CFG;
        require_once($CFG->dirroot . '/local/ace/locallib.php');

        $userenrolmentsalias = $this->get_table_alias('user_enrolments');
        $enrolalias = $this->get_table_alias('enrol');
        $rolealias = $this->get_table_alias('role');
        $userlastaccessalias = $this->get_table_alias('user_lastaccess');
        $contextalias = $this->get_table_alias('context');
        $roleassignmentalias = $this->get_table_alias('role_assignments');
        $groupsalias = $this->get_table_alias('groups');

        $course = \local_ace_get_course_helper();
        $coursejoin = '';
        if (!empty($course) && $course->id !== SITEID) {
            $coursejoin = " AND {$enrolalias}.courseid = {$course->id}";
        }

        $this->add_join("INNER JOIN {enrol} {$enrolalias} ON {$enrolalias}.id = {$userenrolmentsalias}.enrolid".$coursejoin);

        $this->add_join("JOIN {context} {$contextalias} ON {$contextalias}.instanceid = {$enrolalias}.courseid
                        AND {$contextalias}.contextlevel = " . CONTEXT_COURSE . "
                     LEFT JOIN {role_assignments} {$roleassignmentalias} ON {$roleassignmentalias}.contextid = {$contextalias}.id
                        AND {$roleassignmentalias}.userid = {$userenrolmentsalias}.userid
                     LEFT JOIN {role} {$rolealias} ON {$rolealias}.id = {$roleassignmentalias}.roleid");

        // Time enrolment started (user_enrolments.timestart).
        $columns[] = (new column(
            'timestart',
            new lang_string('timestarted', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_fields("$userenrolmentsalias.timestart")
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_callback([format::class, 'userdate']);

        // Time enrolment ended (user_enrolments.timeend).
        $columns[] = (new column(
            'timeend',
            new lang_string('timeend', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_fields("$userenrolmentsalias.timeend")
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_callback([format::class, 'userdate']);

        // Time created (user_enrolments.timecreated).
        $columns[] = (new column(
            'timecreated',
            new lang_string('timecreated', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_fields("$userenrolmentsalias.timecreated")
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_callback([format::class, 'userdate']);

        // Enrol plugin used (mdl_enrol.enrol).
        $columns[] = (new column(
            'enrol',
            new lang_string('enrol', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_is_sortable(true)
            ->add_fields("$enrolalias.enrol");

        // Role given to user (mdl_enrol.roleid - allowing for role shortname.
        $columns[] = (new column(
            'role',
            new lang_string('role', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_is_sortable(true)
            ->add_fields("{$rolealias}.shortname");

        $columns[] = (new column(
            'lastaccessed',
            new lang_string('lastaccessed', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join("LEFT JOIN {user_lastaccess} {$userlastaccessalias}
                        ON {$userlastaccessalias}.userid = {$userenrolmentsalias}.userid
                        AND {$enrolalias}.courseid = {$userlastaccessalias}.courseid")
            ->set_is_sortable(true)
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_fields("$userlastaccessalias.timeaccess")
            ->set_callback([format::class, 'userdate']);

        $columns[] = (new column(
                'groups',
                new lang_string('groups', 'local_ace'),
                $this->get_entity_name()
            ))
                ->add_joins($this->get_joins())
                ->add_join($this->get_group_join())
                ->set_is_sortable(true)
                ->add_fields("{$groupsalias}.name");

        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * Time enrolment started (user_enrolments.timestart)
     * Time enrolment ended (user_enrolments.timeend)
     * Time created (user_enrolments.timecreated),
     * Enrol plugin used (mdl_enrol.enrol)
     * Role given to user (mdl_enrol.roleid - allowing for role shortname.
     * User last access (join with mdl_user_lastaccess table)
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {

        $filters = [];

        $userenrolmentsalias = $this->get_table_alias('user_enrolments');
        $enrolalias = $this->get_table_alias('enrol');
        $rolealias = $this->get_table_alias('role');
        $userlastaccessalias = $this->get_table_alias('user_lastaccess');
        $groupsalias = $this->get_table_alias('groups');

        // Time enrolment started (user_enrolments.timestart).
        $filters[] = (new filter(
            text::class,
            'timestart',
            new lang_string('timestarted', 'local_ace'),
            $this->get_entity_name(),
            "{$userenrolmentsalias}.timestart"
        ))
            ->add_joins($this->get_joins());

        // Time enrolment ended (user_enrolments.timeend).
        $filters[] = (new filter(
            text::class,
            'timeend',
            new lang_string('timeend', 'local_ace'),
            $this->get_entity_name(),
            "{$userenrolmentsalias}.timeend"
        ))
            ->add_joins($this->get_joins());

        // Time created (user_enrolments.timecreated).
        $filters[] = (new filter(
            text::class,
            'timecreated',
            new lang_string('timecreated', 'local_ace'),
            $this->get_entity_name(),
            "{$userenrolmentsalias}.timecreated"
        ))
            ->add_joins($this->get_joins());

        // Enrol plugin used (mdl_enrol.enrol).
        $filters[] = (new filter(
            text::class,
            'enrol',
            new lang_string('enrol', 'local_ace'),
            $this->get_entity_name(),
            "{$enrolalias}.enrol"
        ))
            ->add_joins($this->get_joins());

        // Role given to user (mdl_enrol.roleid - allowing for role shortname.
        $filters[] = (new filter(
            text::class,
            'role',
            new lang_string('role', 'local_ace'),
            $this->get_entity_name(),
            "{$rolealias}.shortname"
        ))
            ->add_joins($this->get_joins());

        // User last access (join with mdl_user_lastaccess table).
        $filters[] = (new filter(
            acedate::class,
            'lastaccess',
            new lang_string('lastaccessedtocourse', 'local_ace'),
            $this->get_entity_name(),
            "{$userlastaccessalias}.timeaccess"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            pagecontextcourse::class,
            'course',
            new lang_string('pagecontextcourse', 'local_ace'),
            $this->get_entity_name(),
            "{$enrolalias}.courseid"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            myenrolledcourses::class,
            'enrolledcourse',
            new lang_string('myenrolledcourses', 'local_ace'),
            $this->get_entity_name(),
            "{$enrolalias}.courseid"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            text::class,
            'groups',
            new lang_string('groups', 'local_ace'),
            $this->get_entity_name(),
            "{$groupsalias}.name"
        ))
            ->add_joins($this->get_joins())
            ->add_join($this->get_group_join());

        return $filters;
    }

    /**
     * Return joins necessary for retrieving groups
     *
     * @return string
     */
    private function get_group_join(): string {
        $userenrolmentsalias = $this->get_table_alias('user_enrolments');
        $groupsalias = $this->get_table_alias('groups');
        $enrolalias = $this->get_table_alias('enrol');

        $course = \local_ace_get_course_helper();
        $coursegroupjoin = '';
        if (!empty($course) && $course->id !== SITEID) {
            $coursegroupjoin = " AND grg.courseid = {$course->id}";
        }

        return "LEFT JOIN (SELECT grg.name, grgm.userid, grg.courseid
                           FROM {groups} grg
                           JOIN {groups_members} grgm on grgm.groupid = grg.id {$coursegroupjoin}) {$groupsalias} ON
                           {$groupsalias}.courseid = {$enrolalias}.courseid 
                           AND {$groupsalias}.userid = {$userenrolmentsalias}.userid";
    }
}
