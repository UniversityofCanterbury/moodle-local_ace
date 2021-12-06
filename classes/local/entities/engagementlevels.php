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
use local_ace\local\filters\engagementlevel;

/**
 * Engagement columns
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

        // We make our own context alias as the course created one is only available in specific contexts.
        $currentengagement = "JOIN {context} {$contextalias} ON {$contextalias}.instanceid = {$coursealias}.id
                                AND {$contextalias}.contextlevel = " . CONTEXT_COURSE . "
                              INNER JOIN {local_ace_samples} {$samplesalias} ON {$samplesalias}.id = (
                                SELECT
                                    s.id
                                FROM
                                    {local_ace_samples} s
                                WHERE
                                    (endtime - starttime = " . $period . ")
                                    AND s.userid = {$useralias}.id
                                    AND s.contextid = {$contextalias}.id
                                ORDER BY
                                    s.id DESC
                                LIMIT 1
                              )";

        $columns[] = (new column(
            'currentengagement',
            new lang_string('currentengagement', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join($currentengagement)
            ->set_is_sortable(true)
            ->add_field("{$samplesalias}.value")
            ->add_callback(static function($value) {
                $value = floatval($value);
                if ($value >= 0.7) {
                    return new lang_string('high', 'local_ace');
                } else if ($value >= 0.3) {
                    return new lang_string('medium', 'local_ace');
                } else {
                    return new lang_string('low', 'local_ace');
                }
            });
        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $samplealias = $this->get_table_alias('local_ace_samples');

        $filters[] = (new filter(
            engagementlevel::class,
            'engagementlevel',
            new lang_string('engagementlevelfilter', 'local_ace'),
            $this->get_entity_name(),
            "{$samplealias}.value"
        ))->add_joins($this->get_joins());
        return $filters;
    }
}
