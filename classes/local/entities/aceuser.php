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
use moodle_url;
use core_user\fields;
use core_reportbuilder\local\report\column;

/**
 * User entity class implementation.
 *
 * This entity defines all the user columns and filters to be used in any report.
 *
 * @package    local_ace
 * @copyright  2021 Canterbury University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class aceuser extends \core_reportbuilder\local\entities\user {

    /**
     * Database tables that this entity uses and their default aliases
     *
     * @return array
     */
    protected function get_default_table_aliases(): array {
        $aliases = parent::get_default_table_aliases();
        $aliases['course'] = 'c';
        return $aliases;
    }

    /**
     * Get all columns
     * @return array
     */
    protected function get_all_columns(): array {
        $columns = parent::get_all_columns();

        $usertablealias = $this->get_table_alias('user');
        $coursetablealias = $this->get_table_alias('course');
        $viewfullnames = self::get_name_fields_select($usertablealias);
        // Fullname column.
        $columns[] = (new column(
            'fullnamedashboardlink',
            new lang_string('fullnamedasboardlink', 'local_ace'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_fields($viewfullnames)
            ->add_field("{$usertablealias}.id")
            ->add_field("{$coursetablealias}.id", 'courseid')
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true)
            ->add_callback(static function(?string $value, \stdClass $row) use ($viewfullnames): string {
                if ($value === null) {
                    return '';
                }

                // Ensure we populate all required name properties.
                $namefields = fields::get_name_fields();
                foreach ($namefields as $namefield) {
                    $row->{$namefield} = $row->{$namefield} ?? '';
                }
                $url = new moodle_url('/local/ace/goto.php', ['userid' => $row->id, 'course' => $row->courseid]);
                return \html_writer::link($url, fullname($row, $viewfullnames));
            });

        return $columns;
    }
}
