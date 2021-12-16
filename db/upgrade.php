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
 * This file keeps track of upgrades to the local_ace
 *
 * @package     local_ace
 * @copyright   2021 University of Canterbury
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrades the plugin.
 * @param int $oldversion
 */
function xmldb_local_ace_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2021101800) {

        set_config('statsrunlast', 0, 'local_ace');

        // Define table local_ace_samples to be created.
        $table = new xmldb_table('local_ace_samples');

        // Adding fields to table local_ace_samples.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, 0);
        $table->add_field('starttime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('endtime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('value', XMLDB_TYPE_NUMBER, '10,2', null, null, null, null);

        // Adding keys to table local_ace_samples.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'), null, null);
        $table->add_key('contextid', XMLDB_KEY_FOREIGN, array('contextid'), 'context', array('id'));

        // Adding indexes to table local_ace_samples.
        $table->add_index('local_ace_samples_userid_idx', XMLDB_INDEX_NOTUNIQUE, ['userid']);
        $table->add_index('local_ace_contextsuser', XMLDB_INDEX_NOTUNIQUE, ['contextid', 'userid']);

        // Conditionally launch create table for local_ace_samples.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table local_ace_contexts to be created.
        $table = new xmldb_table('local_ace_contexts');

        // Adding fields to table local_ace_samples.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, 0);
        $table->add_field('starttime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('endtime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('value', XMLDB_TYPE_NUMBER, '10,2', null, null, null, null);

        // Adding keys to table local_ace_samples.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'), null, null);
        $table->add_key('contextid', XMLDB_KEY_FOREIGN, array('contextid'), 'context', array('id'));

        // Conditionally launch create table for local_ace_samples.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // The plugin ace savepoint reached.
        upgrade_plugin_savepoint(true, 2021101800, 'local', 'ace');
    }

    if ($oldversion < 2021121602) {

        // Define table local_ace_log_summary to be created.
        $table = new xmldb_table('local_ace_log_summary');

        // Adding fields to table local_ace_log_summary.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('viewcount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_ace_log_summary.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table local_ace_log_summary.
        $table->add_index('courseid', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
        $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);

        // Conditionally launch create table for local_ace_log_summary.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Ace savepoint reached.
        upgrade_plugin_savepoint(true, 2021121602, 'local', 'ace');
    }

    return true;
}

