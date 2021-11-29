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

use context_system;
use html_writer;
use lang_string;
use moodle_url;
use stdClass;
use core_user\fields;
use core_reportbuilder\local\filters\boolean_select;
use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\filters\select;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\filters\user as user_filter;
use core_reportbuilder\local\helpers\user_profile_fields;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;

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
     * Get all columns
     * @return array
     */
    protected function get_all_columns(): array {
        $columns = parent::get_all_columns();

        $usertablealias = $this->get_table_alias('user');
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
                $url = new moodle_url('/local/ace/goto.php', ['userid' => $row->id]);
                return \html_writer::link($url, fullname($row, $viewfullnames));
            });

        return $columns;
    }
}
