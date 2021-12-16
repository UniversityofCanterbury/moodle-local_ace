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
 * Tasks
 *
 * @package     local_ace
 * @copyright   2021 University of Canterbury
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ace\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Used to get stats for logs to use in reports.
 */
class log_summary extends \core\task\scheduled_task {
    /**
     * Returns the name of this task.
     */
    public function get_name() {
        // Shown in admin screens.
        return get_string('logsummary', 'local_ace');
    }
    /**
     * Executes task.
     */
    public function execute() {
        global $DB;
        $DB->delete_records('local_ace_log_summary');

        $sql = "INSERT INTO {local_ace_log_summary} (courseid, cmid, userid, viewcount)
                 (SELECT courseid,
                        contextinstanceid as cmid,
                        userid,
                        count(*) AS viewcounttotal
                        FROM {logstore_standard_log}
                       WHERE contextlevel = ".CONTEXT_MODULE ." AND crud = 'r'
                   GROUP BY courseid, contextinstanceid, userid)";

        $DB->execute($sql);
    }
}
