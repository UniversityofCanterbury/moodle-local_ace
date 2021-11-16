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

use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\filters\number;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use core_reportbuilder\local\entities\base;
use lang_string;

defined('MOODLE_INTERNAL') || die();


/**
 * acesamples entity class implementation
 *
 * This entity defines all the ACE sample columns and filters to be used in any report.
 *
 * @package     local_ace
 * @copyright   2021 University of Canterbury
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class acesamples extends base {

    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {
        return [
            'local_ace_samples' => 'aslas',
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('sampleentitytitle', 'local_ace');
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

        $samplesalias = $this->get_table_alias('local_ace_samples');

        // Module starttime column.
        $columns[] = (new column(
            'starttime',
            new lang_string('starttime', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_is_sortable(true)
            ->add_field("{$samplesalias}.starttime")
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_callback([format::class, 'userdate']);

        // Time enrolment ended.
        $columns[] = (new column(
            'endtime',
            new lang_string('endtime', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_fields("{$samplesalias}.endtime")
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_callback([format::class, 'userdate']);

        // Student engagement percent value.
        $columns[] = (new column(
            'studentengagement',
            new lang_string('studentengagement', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true)
            ->add_fields("{$samplesalias}.value");

        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {

        $filters = [];
        $samplesalias = $this->get_table_alias('local_ace_samples');

        // Start time filter.
        $filters[] = (new filter(
            date::class,
            'starttime',
            new lang_string('starttime', 'local_ace'),
            $this->get_entity_name(),
            "{$samplesalias}.starttime"
        ))
            ->add_joins($this->get_joins());

        // End Time  filter.
        $filters[] = (new filter(
            date::class,
            'endtime',
            new lang_string('endtime', 'local_ace'),
            $this->get_entity_name(),
            "{$samplesalias}.endtime"
        ))
            ->add_joins($this->get_joins());

        // Engagement value filter.
        $filters[] = (new filter(
            number::class,
            'studentengagement',
            new lang_string('studentengagement', 'local_ace'),
            $this->get_entity_name(),
            "{$samplesalias}.value"
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }
}
