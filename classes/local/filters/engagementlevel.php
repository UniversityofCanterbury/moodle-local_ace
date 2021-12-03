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

namespace local_ace\local\filters;

use MoodleQuickForm;

/**
 * Adds a select for choosing the engagement levels to show (low/medium/high).
 *
 * @package     local_ace
 * @copyright   2021 University of Canterbury
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class engagementlevel extends \core_reportbuilder\local\filters\base {

    /**
     * Adds controls specific to this filter in the form.
     *
     * @param MoodleQuickForm $mform
     */
    public function setup_form(MoodleQuickForm $mform): void {
        $element = $mform->addElement('select', "{$this->name}_engagementlevel", '', [
            0 => get_string('low', 'local_ace'),
            1 => get_string('medium', 'local_ace'),
            2 => get_string('high', 'local_ace')
        ]);
        $element->setMultiple(true);
    }

    /**
     * Return filter SQL
     *
     * @param array $values
     * @return array array of two elements = SQL query and named parameters
     */
    public function get_sql_filter(array $values): array {
        if (!$values) {
            return ['', []];
        }
        if (isset($values["{$this->name}_engagementlevel"])) {
            $levels = $values["{$this->name}_engagementlevel"];
            $fieldsql = $this->filter->get_field_sql();
            $sql = [];
            foreach ($levels as $level) {
                if ($level == 2) {
                    $sql[] = "($fieldsql >= 0.7)";
                }
                if ($level == 1) {
                    $sql[] = "($fieldsql >= 0.3 AND $fieldsql < 0.7)";
                }
                if ($level == 0) {
                    $sql[] = "($fieldsql < 0.3)";
                }
            }
            $sql = implode(' OR ', $sql);
            return [$sql, []];
        }
        return ['', []];
    }
}
