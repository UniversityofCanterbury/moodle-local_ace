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

use context_course;
use context_helper;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use html_writer;
use lang_string;
use local_ace\local\filters\courseselect;
use stdClass;
use moodle_url;
use local_ace\local\filters\pagecontextcourse;
use local_ace\local\filters\myenrolledcourses;
use local_ace\local\filters\courseregex;

defined('MOODLE_INTERNAL') || die();

/**
 * Course entity class implementation
 *
 * This entity defines all the course columns and filters to be used in any report.
 *
 * @package     local_ace
 * @copyright   2021 University of Canterbury
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class acecourse extends \core_reportbuilder\local\entities\course {
    /**
     * Add extra columns to course report.
     * @return array
     * @throws \coding_exception
     */
    protected function get_all_columns(): array {
        $columns = parent::get_all_columns();
        $tablealias = $this->get_table_alias('course');
        $column = (new column(
            'courseshortnamedashboardlink',
            new lang_string('courseshortnamedashboardlink', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tablealias}.shortname as courseshortnamedashboardlink, {$tablealias}.id")
            ->set_is_sortable(true)
            ->add_callback(static function(?string $value, stdClass $row): string {
                if ($value === null) {
                    return '';
                }

                context_helper::preload_from_record($row);
                $url = new moodle_url('/local/ace/goto.php', ['course' => $row->id]);
                return html_writer::link($url,
                    format_string($value, true, ['context' => context_course::instance($row->id)]));
            });

        $columns[] = $column;
        return $columns;
    }

    /**
     * Get all filters.
     * @return array
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    protected function get_all_filters(): array {
        $filters = parent::get_all_filters();

        $tablealias = $this->get_table_alias('course');

        $filters[] = (new filter(
            pagecontextcourse::class,
            'course',
            new lang_string('pagecontextcourse', 'local_ace'),
            $this->get_entity_name(),
            "{$tablealias}.id"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            myenrolledcourses::class,
            'enrolledcourse',
            new lang_string('myenrolledcourses', 'local_ace'),
            $this->get_entity_name(),
            "{$tablealias}.id"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            courseregex::class,
            'courseregex',
            new lang_string('courseregex', 'local_ace'),
            $this->get_entity_name(),
            "{$tablealias}.shortname"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            courseselect::class,
            'courseselect',
            new lang_string('courseselect', 'local_ace'),
            $this->get_entity_name(),
            "{$tablealias}.id"
        ))->add_joins($this->get_joins());

        return $filters;
    }
}
