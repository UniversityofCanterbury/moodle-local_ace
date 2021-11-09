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

/**
 * A page that provides an admin bulk email messaging.
 *
 * @package    local_ace
 * @copyright   2021 University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once("$CFG->libdir/formslib.php");
require_once($CFG->dirroot . '/local/ace/locallib.php');

require_login();

$PAGE->set_url('/local/ace/bulkaction.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_heading(get_string("bulkemailheading", "local_ace"));

$userids = optional_param_array('id', null, PARAM_INT);
$downloadselected = optional_param('emailallselected', '', PARAM_TEXT);

// Get the view page url to return to.
if (get_local_referer(false) != null) {

    $PAGE->navbar->add(get_string("bulkemailbreadcrumbs", "local_ace"), new moodle_url(get_local_referer(false)));

    if (strpos(get_local_referer(false), 'id=') !== false) {
        $SESSION->wantsurl = get_local_referer(false);
    }
}

// Add id's to session.
if (!empty($userids)) {
    $SESSION->userids = $userids;
}

require_once('classes/forms/bulkaction_form.php');

$mform = new bulkaction_form();

if ($mform->is_cancelled()) { // If cancelled, redirect back to report view.

    redirect($SESSION->wantsurl, null, null, null);

} else if ($mform->get_data()) { // If submitted, send emails to all users selected.

    $emailsubject = '';
    $emailmessage = '';
    $data = $mform->get_data();

    if (isset($data->emailsubject)) {
        $emailsubject = $data->emailsubject;
    }

    if (isset($data->emailmessage)) {
        $emailmessage = $data->emailmessage;
    }

    if ($emailsubject && $emailmessage) {
        if (isset($SESSION->userids)) {
            $bulkemail = local_ace_send_bulk_email($SESSION->userids, $emailsubject, $emailmessage);
        }
    }

    // Notify and redirect admin user back to the report view.
    if ($bulkemail) {
        redirect($SESSION->wantsurl, get_string("emailsent", "local_ace"), null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        redirect($SESSION->wantsurl, get_string("emailfailed", "local_ace"), null, \core\output\notification::NOTIFY_ERROR);
    }

    echo $OUTPUT->header();

} else { // On load show the email message form.

    echo $OUTPUT->header();
    $mform->display();

}

echo $OUTPUT->footer();
