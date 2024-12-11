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
 * Upgrade scripts for course format "Drip"
 *
 * @package   format_drip
 * @copyright 2020-2024 onwards Solin (https://solin.co)
 * @author    Denis (denis@solin.co)
 * @author    Martijn (martijn@solin.nl)
 * @author    Onno (onno@solin.co)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade script for format_drip.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool True on success.
 */
function xmldb_format_drip_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // Get the database manager.

    if ($oldversion < 2024081404) {
        // Define table format_drip_email_log to be created.
        $table = new xmldb_table('format_drip_email_log');

        // Define fields.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        // The ID of the user who received the email notification.
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
        $table->add_field('sectionid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);

        // Define keys and indexes.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('userid_sectionid', XMLDB_INDEX_UNIQUE, ['userid', 'sectionid']);

        // Conditionally launch table creation.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Update plugin savepoint.
        upgrade_plugin_savepoint(true, 2024081404, 'format', 'drip');
    }

    return true;
}
