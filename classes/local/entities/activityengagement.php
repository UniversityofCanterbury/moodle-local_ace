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
        global $PAGE;

        try {
            $context = $PAGE->context;
            if ($context->contextlevel === CONTEXT_MODULE) {
                list($course, $cm) = get_course_and_cm_from_cmid($context->instanceid);
            }
            // @codingStandardsIgnoreStart
        } catch (\coding_exception $ignored) {
        }
        // @codingStandardsIgnoreStart

        $useralias = $this->get_table_alias('user');
        $totalaccessalias = $this->get_table_alias('totalaccess');
        $logalias = $this->get_table_alias('logstore_standard_log');

        $lastaccessjoin = "LEFT JOIN {logstore_standard_log} {$logalias} ON {$logalias}.id = (
                                SELECT
                                    id
                                FROM
                                    {logstore_standard_log}
                                WHERE
                                    courseid = " . ($course->id ?? 'NULL') . "
                                    AND contextid = " . ($context->id ?? 'NULL') . "
                                    AND userid = {$useralias}.id
                                ORDER BY
                                    id DESC
                                LIMIT 1)";
        $totalaccessjoin = "LEFT JOIN (
                                SELECT COUNT(id), userid
                                FROM {logstore_standard_log}
                                WHERE courseid = " . ($course->id ?? 'NULL') . " AND contextid = " . ($context->id ?? 'NULL') . "
                                    AND contextlevel = " . CONTEXT_MODULE . " AND crud = 'r'
                                GROUP BY userid)
                            {$totalaccessalias} ON {$totalaccessalias}.userid = {$useralias}.id";

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
            ->add_callback(static function($value): string {
                if ($value == null) {
                    return '0';
                }
                return $value;
            });

        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $tablealias = $this->get_table_alias('course_modules');

        $filters[] = (new filter(
            pagecontextactivity::class,
            'activity',
            new lang_string('pagecontextactivity', 'local_ace'),
            $this->get_entity_name(),
            "{$tablealias}.id"
        ))->add_joins($this->get_joins());

        return $filters;
    }
}
