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
 * Current engagement columns
 *
 * @package    local_ace
 * @copyright  2021 University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class engagementlevels extends base {

    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {
        return [
            'local_ace_samples' => 'aslas',
            'user' => 'u',
            'course' => 'c',
            'context' => 'acectx'
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('engagementlevelstitle', 'local_ace');
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
        $config = get_config('local_ace');
        $period = (int) $config->displayperiod;

        $samplesalias = $this->get_table_alias('local_ace_samples');
        $useralias = $this->get_table_alias('user');
        $contextalias = $this->get_table_alias('context');
        $coursealias = $this->get_table_alias('course');

        $currentengagement = "INNER JOIN mdl_local_ace_samples samples ON samples.id = (
        SELECT
            s.id
        FROM
            mdl_local_ace_samples s
            JOIN mdl_context cx ON s.contextid = cx.id
            AND cx.contextlevel = 50
            JOIN mdl_course co ON cx.instanceid = co.id
        WHERE
            (endtime - starttime = 259200)
            AND s.userid = u.id
            AND s.contextid = cctx.id
        ORDER BY
            s.id DESC
            LIMIT 1
    )";

        //$currentengagement = "JOIN {context} {$contextalias
        //                        ON {$contextalias}.instanceid = {$coursealias}.id
        //                        AND {$contextalias}.contextlevel = 50";

        $columns[] = (new column(
            'currentengagement',
            new lang_string('currentengagement', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join($currentengagement)
            ->set_is_sortable(true)
            ->add_field("samples.value");
        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        return [];
    }

    /**
     * User fields
     *
     * @return lang_string[]
     */
    protected function get_user_fields(): array {
        return [];
    }
}
