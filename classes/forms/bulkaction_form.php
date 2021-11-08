<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Bulk actions message form.
 *
 * @package     local_ace
 * @copyright   2021 University of Canterbury
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

/**
 * Bulk action message form.
 *
 * @package     local_ace
 * @copyright   2021 University of Canterbury
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bulkaction_form extends moodleform {

    /**
     * Define a bulk action messaging form and action buttons.
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Email subject.
        $mform->addElement('text', 'emailsubject', get_string("emailsubject", "local_ace"));
        $mform->setType('emailsubject', PARAM_TEXT);
        $mform->addRule('emailsubject', get_string('required'), 'required');

        // Email message.
        $mform->addElement('textarea', 'emailmessage', get_string("emailtext", "local_ace"), 'wrap="virtual" rows="10" cols="50"');
        $mform->setType('emailmessage', PARAM_TEXT);
        $mform->addRule('emailmessage', get_string('required'), 'required');

        $this->add_action_buttons();
    }
}
