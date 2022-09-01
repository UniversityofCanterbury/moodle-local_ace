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

use context_module;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use lang_string;
use local_ace\local\filters\pagecontextactivity;

/**
 * Columns/filters for the user activity report.
 *
 * @package     local_ace
 * @copyright   2021 University of Canterbury
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activityengagement extends base {

    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {
        return [
            'course_modules' => 'cm',
            'course' => 'c',
            'user_enrolments' => 'ue',
            'enrol' => 'eel',
            'context' => 'ctx',
            'user' => 'u',
            'totalaccess' => 'tolac',
            'totalwrites' => 'tolwr',
            'logstore_standard_log' => 'lssl',
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('activityengagement', 'local_ace');
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
        global $CFG;
        require_once($CFG->dirroot . '/local/ace/locallib.php');
        $coursemoduleid = local_ace_get_coursemodule_helper();
        if (!empty($coursemoduleid)) {
            $context = context_module::instance($coursemoduleid);
            list($course, $cm) = get_course_and_cm_from_cmid($coursemoduleid);
        }

        $useralias = $this->get_table_alias('user');
        $this->add_selectable_column($useralias);
        $totalaccessalias = $this->get_table_alias('totalaccess');
        $totalwritesalias = $this->get_table_alias('totalwrites');
        $logalias = $this->get_table_alias('logstore_standard_log');

        $lastaccessjoin = "LEFT JOIN (SELECT max(timecreated) as timecreated, userid
                                        FROM {logstore_standard_log}
                                       WHERE courseid = " . ($course->id ?? 'NULL') . " AND
                                             contextid = " . ($context->id ?? 'NULL') . "
                                    GROUP BY userid
                                      )  {$logalias} on {$logalias}.userid = {$useralias}.id";
        $totalaccessjoin = "LEFT JOIN (
                                SELECT COUNT(id), userid
                                FROM {logstore_standard_log}
                                WHERE courseid = " . ($course->id ?? 'NULL') . " AND contextid = " . ($context->id ?? 'NULL') . "
                                    AND contextlevel = " . CONTEXT_MODULE . " AND crud = 'r'
                                GROUP BY userid)
                            {$totalaccessalias} ON {$totalaccessalias}.userid = {$useralias}.id";

        $totalwritesjoin = "LEFT JOIN (
                                SELECT COUNT(id) as readactions, userid
                                FROM {logstore_standard_log}
                                WHERE courseid = " . ($course->id ?? 'NULL') . " AND contextid = " . ($context->id ?? 'NULL') . "
                                    AND contextlevel = " . CONTEXT_MODULE . " AND (crud = 'c' OR crud = 'u' OR crud = 'd')
                                GROUP BY userid)
                            {$totalwritesalias} ON {$totalwritesalias}.userid = {$useralias}.id";

        $columns[] = (new column(
            'lastaccess',
            new lang_string('lastaccess'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join($lastaccessjoin)
            ->set_is_sortable(true)
            ->add_field("{$logalias}.timecreated")
            ->add_callback(static function($value): string {
                if ($value == null) {
                    return get_string('never');
                }
                return userdate($value, get_string('strftimedate'));
            });

        $columns[] = (new column(
            'totalaccesses',
            new lang_string('totalaccesses', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join($totalaccessjoin)
            ->set_is_sortable(true)
            ->add_field("{$totalaccessalias}.count")
            ->set_type(column::TYPE_INTEGER);

        $columns[] = (new column(
            'totalwrites',
            new lang_string('totalwrites', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join($totalwritesjoin)
            ->set_is_sortable(true)
            ->add_field("{$totalwritesjoin}.readactions")
            ->set_type(column::TYPE_INTEGER);

        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $tablealias = $this->get_table_alias('course_modules');
        $logalias = $this->get_table_alias('logstore_standard_log');

        $filters[] = (new filter(
            pagecontextactivity::class,
            'activity',
            new lang_string('pagecontextactivity', 'local_ace'),
            $this->get_entity_name(),
            "{$tablealias}.id"
        ))->add_joins($this->get_joins());

        $filters[] = (new filter(
            date::class,
            'lastaccess',
            new lang_string('lastaccess'),
            $this->get_entity_name(),
            "{$logalias}.timecreated"
        ))->add_joins($this->get_joins());

        return $filters;
    }
}
