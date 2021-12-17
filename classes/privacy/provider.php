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
 * Privacy Subsystem implementation for local_ace.
 *
 * @package     local_ace
 * @copyright   2021 University of Canterbury
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ace\privacy;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\{writer, helper, contextlist, approved_contextlist, approved_userlist, userlist};

defined('MOODLE_INTERNAL') || die();
/**
 * Privacy Subsystem for local_ace implementing provider.
 *
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\user_preference_provider {

    /**
     * Returns metadata.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'local_ace_samples',
             [
                'userid' => 'privacy:metadata:local_ace:userid',
                'starttime' => 'privacy:metadata:local_ace:starttime',
                'endtime' => 'privacy:metadata:local_ace:endtime',
                'value' => 'privacy:metadata:local_ace:value',
             ], 'privacy:metadata:local_ace'
        );

        $collection->add_database_table(
            'local_ace_log_summary',
            [
                'courseid' => 'privacy:metadata:courseid',
                'cmid' => 'privacy:metadata:cmid',
                'userid' => 'privacy:metadata:userid',
                'viewcount' => 'privacy:metadata:local_ace:viewcount',
            ], 'privacy:metadata:local_ace_log_summary'
        );

        $collection->add_user_preference(
            'local_ace_teacher_hidden_courses', 'privacy:metadata:preference:localaceteacherhiddencourses'
        );
        $collection->add_user_preference(
            'local_ace_comparison_method', 'privacy:metadata:preference:localacecomparisonmethod'
        );

        return $collection;
    }

    /**
     * Export all user preferences for the plugin.
     *
     * @param   int         $userid The userid of the user whose data is to be exported.
     */
    public static function export_user_preferences(int $userid) {
        $teacherhiddencourses = get_user_preferences('local_ace_teacher_hidden_courses', null, $userid);
        if ($teacherhiddencourses !== null) {
            writer::export_user_preference('local_ace',
                'local_ace_teacher_hidden_courses',
                transform::yesno($teacherhiddencourses),
                get_string('privacy:metadata:preference:localaceteacherhiddencourses', 'local_ace')
            );
        }
        $comparisonmethod = get_user_preferences('local_ace_comparison_method', null, $userid);
        if ($comparisonmethod !== null) {
            writer::export_user_preference('local_ace',
                'local_ace_comparison_method',
                transform::yesno($comparisonmethod),
                get_string('privacy:metadata:preference:localacecomparisonmethod', 'local_ace')
            );
        }
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_course) {
            return;
        }
        $sql = "SELECT userid
                FROM {local_ace_samples}
                WHERE contextid = :cx";
        $params = ['cx' => $context->id];
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();
        if (!$context instanceof \context_course) {
            return;
        }
        // Prepare SQL to gather all completed IDs.
        $userids = $userlist->get_userids();
        list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $inparams['cx'] = $context->id;
        $DB->delete_records_select(
            'local_ace_samples',
            "contextid = :cx AND userid $insql",
            $inparams
        );

        list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $DB->delete_records_select(
            'local_ace_log_summary',
            "userid $insql",
            $inparams
        );
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * In the case of attendance, that is any attendance where a student has had their
     * attendance taken or has taken attendance for someone else.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        return (new contextlist)->add_from_sql(
            "SELECT contextid
                 FROM {local_ace_samples} cm
                 WHERE userid = :userid",
            [
                'userid' => $userid,
            ]
        );
    }

    /**
     * Delete all personal data for all users in the specified context.
     *
     * @param context $context Context to delete data from.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        $DB->delete_records('local_ace_samples', ['contextid' => $context->id]);
    }

    /**
     * Delete all information recorded against sessions associated with this context.
     *
     * @param approved_contextlist $contextlist The approved contextlist to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = (int)$contextlist->get_user()->id;
        $params = ['userid' => $userid];
        $contextids = $contextlist->get_contextids();
        list($insql, $inparams) = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED);

        $DB->delete_records_select(
            'local_ace_samples',
            "userid = :userid AND (contexid $insql)",
            $inparams + $params
        );

        $DB->delete_records_select(
            'local_ace_log_summary',
            "userid = :userid",
            $params
        );
    }

    /**
     * Export all user data for the specified user, in the specified contexts, using the supplied exporter instance.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $data = [];

        $userid = (int)$contextlist->get_user()->id;
        $results = $DB->get_records('local_ace_samples', array('userid' => $userid));
        foreach ($results as $result) {
            $data[] = (object) [
                'starttime' => $result->starttime,
                'endtime' => $result->endtime,
                'value' => $result->value,
            ];
        }
        if (!empty($data)) {
            $data = (object) [
                'ucanlytics' => $data,
            ];
            \core_privacy\local\request\writer::with_context($contextlist->current())->export_data([
                get_string('pluginname', 'local_ace')], $data);
        }
    }
}

