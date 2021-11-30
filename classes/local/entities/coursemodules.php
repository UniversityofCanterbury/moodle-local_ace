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

use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use core_reportbuilder\local\entities\base;
use lang_string;
use local_ace\local\filters\coursemoduletype;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/local/ace/lib.php');

/**
 * Course entity class implementation
 *
 * This entity defines all the course columns and filters to be used in any report.
 *
 * @package     local_ace
 * @copyright   2021 University of Canterbury
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coursemodules extends base {

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
                'totalviewcount' => 'cmtvc',
                'totalviewcountuser' => 'cmtvcu',
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
        global $USER, $PAGE;
        // Note this custom report source is restricted to showing activities.
        $course = local_ace_get_course_from_url_or_refer();
        if (!empty($course)) {
            $courseid = $course->id;
        } else {
            $courseid = 0; // Should not happen when using this entity correctly, set to 0 to prevent SQL dying.
        }

        // Determine which user to use within the user specific columns - use $PAGE->context if user context or global $USER.
        $userid = $USER->id;
        if (!empty($PAGE->context) && $PAGE->context->contextlevel == CONTEXT_USER) {
            $userid = $PAGE->context->instanceid;
        } else if (!empty($PAGE) &&
            $PAGE->state != \moodle_page::STATE_BEFORE_HEADER ) { // When building a report $PAGE doesn't really exist.
            $coursecontext = $PAGE->context->get_course_context(false);
            if (!empty($coursecontext)) {
                $courseid = $coursecontext->instanceid;
            }
        }
        $cmalias = $this->get_table_alias('course_modules');
        $modulesalias = $this->get_table_alias('modules');
        $totalviewcountalias = $this->get_table_alias('totalviewcount');
        $totalviewcountuseralias = $this->get_table_alias('totalviewcountuser');

        $this->add_join("JOIN {modules} {$modulesalias} ON {$cmalias}.module = {$modulesalias}.id");

        // Get list of modules we want to include in this query.
        $modules = \local_ace_get_module_types();

        // Create a table with the instanceid, module id and activity name to match with coursemodule table.
        $modulejoins = [];
        foreach ($modules as $mid => $mname) {
            $duedatecolumn = 0; // Where the activity doesn't have a duedate we prefill this param as empty.
            if ($mname == 'assign') {
                $duedatecolumn = 'duedate';
            }
            // This injects params into in-line sql, but we cast and clean all to make safe.
            $modulejoins[] = "SELECT id, name, $mid as module, $duedatecolumn as duedate
                                FROM {".$mname."}
                               WHERE course = $courseid";
        }
        $modulejoin = implode(' UNION ALL ', $modulejoins);
        $this->add_join("JOIN ({$modulejoin}) mmj ON mmj.id = {$cmalias}.instance AND mmj.module = {$cmalias}.module");

        $columns = [];

        // Module Icon column.
        $columns[] = (new column(
            'type',
            new lang_string('activity'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_is_sortable(true)
            ->add_field("{$modulesalias}.name")
            ->add_callback(static function ($v): string {
                global $OUTPUT;
                return $OUTPUT->pix_icon('icon', $v, $v, array('class' => 'icon'));
            });

        // Module Icon column.
        $columns[] = (new column(
            'name',
            new lang_string('name'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_is_sortable(true)
            ->add_field("mmj.name");

        // Date due.
        $columns[] = (new column(
            'duedate',
            new lang_string('due', 'local_ace'),
            $this->get_entity_name()
        ))
        ->add_joins($this->get_joins())
        ->set_is_sortable(true)
        ->set_type(column::TYPE_TIMESTAMP)
        ->add_fields("mmj.duedate")
        ->set_callback([format::class, 'userdate']);

        $viewcountsql = "LEFT JOIN (SELECT COUNT(id) as viewcounttotal, contextinstanceid
                                 FROM {logstore_standard_log}
                                WHERE courseid = $courseid
                                      AND contextlevel = ".CONTEXT_MODULE."
                                      AND crud = 'r'
                             GROUP BY contextinstanceid) {$totalviewcountalias}
                             ON {$totalviewcountalias}.contextinstanceid = {$cmalias}.id";

        $columns[] = (new column(
            'viewcounttotal',
            new lang_string('totalviews', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_join($viewcountsql)
            ->set_is_sortable(true)
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$totalviewcountalias}.viewcounttotal");

        $viewcountusersql = "LEFT JOIN (SELECT COUNT(id) as viewcounttotal, contextinstanceid
                                 FROM {logstore_standard_log}
                                WHERE courseid = $courseid AND userid = $userid
                                      AND contextlevel = ".CONTEXT_MODULE."
                                      AND crud = 'r'
                             GROUP BY contextinstanceid) {$totalviewcountuseralias}
                             ON {$totalviewcountuseralias}.contextinstanceid = {$cmalias}.id";

        $columns[] = (new column(
            'viewcounttotaluser',
            new lang_string('totalviewsuser', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_join($viewcountusersql)
            ->set_is_sortable(true)
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$totalviewcountuseralias}.viewcounttotal");

        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {

        $filters = [];
        $cmalias = $this->get_table_alias('course_modules');
        $modulesalias = $this->get_table_alias('modules');

        // Module name filter.
        $filters[] = (new filter(
            text::class,
            'nameselector',
            new lang_string('name'),
            $this->get_entity_name(),
            "{$modulesalias}.name"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            coursemoduletype::class,
            'moduletype',
            new lang_string('moduletype', 'local_ace'),
            $this->get_entity_name(),
            "{$cmalias}.module"
        ))->add_joins($this->get_joins());

        return $filters;
    }
}
