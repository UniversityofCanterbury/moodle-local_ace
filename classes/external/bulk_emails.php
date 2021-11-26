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
 * External functions for sending bulk emails
 *
 * @package     local_ace
 * @copyright   2021 University of Canterbury
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/local/ace/locallib.php');

/**
 * Class send_bulk_emails
 *
 * @package     local_ace
 * @copyright   2021 University of Canterbury
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bulk_emails extends external_api {

    /**
     * Returns parameter types for send_bulk_emails function.
     *
     * @return external_function_parameters
     */
    public static function send_bulk_emails_parameters(): external_function_parameters {
        return new external_function_parameters(
            array(
                'userids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'User ID'),
                    'Array of user IDs'
                ),
                'subject' => new external_value(PARAM_TEXT, 'Email subject'),
                'body' => new external_value(PARAM_TEXT, 'Email body')
            )
        );
    }

    /**
     * Send bulk email to specified users.
     *
     * @param array $userids
     * @param string $subject
     * @param string $body
     * @return array
     */
    public static function send_bulk_emails(array $userids, string $subject, string $body): array {
        global $PAGE;

        $params = self::validate_parameters(
            self::send_bulk_emails_parameters(),
            array(
                'userids' => $userids,
                'subject' => $subject,
                'body' => $body
            )
        );

        $context = context_system::instance();
        $PAGE->set_context($context);

        if (!has_capability('local/ace:sendbulkemails', $context)) {
            return ['message' => get_string('nopermissions', 'error',
                'local/ace:sendbulkemails')];
        }

        $count = local_ace_send_bulk_email($params['userids'], $params['subject'], $params['body']);
        if ($count == 0) {
            // No emails were sent.
            return ['message' => get_string('emailfailed', 'local_ace')];
        } else if (count($userids) === $count) {
            // All emails were sent.
            return ['message' => get_string('emailsent', 'local_ace')];
        }
        // Only a portion of the emails were sent.
        return ['message' => get_string('emailportionfailed', 'local_ace')];
    }

    /**
     * Returns message describing result of send_bulk_emails()
     *
     * @return external_single_structure
     */
    public static function send_bulk_emails_returns(): external_single_structure {
        return new external_single_structure([
            'message' => new external_value(PARAM_TEXT, 'Return message')
        ]);
    }

}
