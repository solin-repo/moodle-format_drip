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
 * @copyright 2020 - 2024 onwards Solin (https://solin.co)
 * @author    Denis (denis@solin.co)
 * @author    Onno (onno@solin.co)
 * @author    Martijn (martijn@solin.nl)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade script for format_drip
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_format_drip_upgrade($oldversion) {
    global $DB;

    // Ensure this block is only run if the version is less than 2024081404.
    if ($oldversion < 2024081404) {
        // Path to the install.xml file.
        $file = __DIR__ . '/install.xml';

        // Table name to create.
        $tablename = 'format_drip_email_log';

        // Create the new table using the install_one_table_from_xmldb_file function.
        $DB->get_manager()->install_one_table_from_xmldb_file($file, $tablename);

        // Mark the new version as the current savepoint.
        upgrade_plugin_savepoint(true, 2024081404, 'format', 'drip');
    }

    return true;
}
