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
 * Privacy Subsystem implementation for Drip course format.
 *
 * @package   format_drip
 * @copyright 2020-2024 onwards Solin (https://solin.co)
 * @author    Denis (denis@solin.co)
 * @author    Martijn (martijn@solin.nl)
 * @author    Onno (onno@solin.co)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_drip\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;

/**
 * Privacy provider implementation for the Drip course format.
 *
 * This class implements the Privacy API for the Drip course format plugin,
 * handling the export, deletion, and management of user data in compliance
 * with privacy requirements.
 *
 * @package   format_drip
 * @category  privacy
 * @copyright 2020-2024 Solin
 * @author    Onno (onno@solin.co)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\request\core_user_data_provider {

    /**
     * Returns metadata about the information stored by the plugin.
     *
     * @param collection $collection The initialised metadata collection to add items to.
     * @return collection The updated metadata collection.
     */
    public static function get_metadata(collection $items): collection {
        $items->add_database_table('format_drip_email_log', [
            'userid' => 'privacy:metadata:userid',
            'sectionid' => 'privacy:metadata:sectionid',
            'timecreated' => 'privacy:metadata:timecreated',
        ], 'privacy:metadata:format_drip_email_log');

        return $items;
    }

    /**
     * Gets the contexts that contain user information for the given user ID.
     *
     * @param int $userid The user ID to retrieve contexts for.
     * @return contextlist A contextlist containing the contexts with user information.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_sections} cs ON cs.course = ctx.instanceid
                  JOIN {format_drip_email_log} fd ON fd.sectionid = cs.id
                 WHERE ctx.contextlevel = :contextlevel
                   AND fd.userid = :userid";
        $params = [
            'contextlevel' => CONTEXT_COURSE,
            'userid' => $userid,
        ];
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Exports all user data for the approved contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export user data for.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel === CONTEXT_COURSE) {
                // Retrieve the course ID from the context.
                $courseid = $context->instanceid;

                // Get section IDs tied to this course and user.
                $sql = "SELECT l.sectionid
                          FROM {format_drip_email_log} l
                          JOIN {course_sections} cs ON l.sectionid = cs.id
                         WHERE cs.course = :courseid
                           AND l.userid = :userid";
                $sectionids = $DB->get_fieldset_sql($sql, [
                    'courseid' => $courseid,
                    'userid' => $userid,
                ]);

                foreach ($sectionids as $sectionid) {
                    // Retrieve records for the current section ID.
                    $data = $DB->get_records('format_drip_email_log', [
                        'sectionid' => $sectionid,
                        'userid' => $userid,
                    ]);

                    // Export the data for this section.
                    \core_privacy\local\request\writer::with_context($context)->export_data(
                        ['section' => $sectionid],
                        (object)['emails' => $data]
                    );
                }
            }
        }
    }

    /**
     * Deletes all user data for a specific user in the given contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to delete user data from.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel === CONTEXT_COURSE) {
                // Retrieve the course ID from the context instance.
                $courseid = $context->instanceid;

                // Delete data related to this user and course.
                $DB->delete_records_select(
                    'format_drip_email_log',
                    'userid = :userid AND sectionid IN (SELECT id FROM {course_sections} WHERE course = :courseid)',
                    ['userid' => $userid, 'courseid' => $courseid]
                );
            }
        }
    }

    /**
     * Deletes all user data for all users in the given context.
     *
     * @param \context $context The context to delete user data from.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel === CONTEXT_COURSE) {
            // Retrieve the course ID from the context instance.
            $courseid = $context->instanceid;

            // Delete data related to sections in this course.
            $DB->delete_records_select(
                'format_drip_email_log',
                'sectionid IN (SELECT id FROM {course_sections} WHERE course = :courseid)',
                ['courseid' => $courseid]
            );
        }
    }

    /**
     * Deletes user data for a list of approved users in a context.
     *
     * @param approved_userlist $userlist The approved user list to delete data for.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();

        // Ensure the context is a course context.
        if ($context->contextlevel === CONTEXT_COURSE) {
            // Retrieve the course ID from the context.
            $courseid = $context->instanceid;

            // Get section IDs for this course.
            $sectionids = $DB->get_fieldset_select('course_sections', 'id', 'course = :courseid', ['courseid' => $courseid]);

            // Delete records for each user in the userlist and the relevant section IDs.
            foreach ($userlist->get_userids() as $userid) {
                $DB->delete_records_select('format_drip_email_log',
                    'userid = :userid AND sectionid IN (' . implode(',', $sectionids) . ')', ['userid' => $userid]);
            }
        }
    }

    /**
     * Gets a list of users who have data in the given context.
     *
     * @param \context $context The context to search for users.
     * @param userlist $userlist The user list to append users to.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();

        $sql = "SELECT DISTINCT fd.userid
                  FROM {format_drip_email_log} fd
                  JOIN {course_sections} cs ON cs.id = fd.sectionid
                 WHERE cs.course = :courseid";
        $params = ['courseid' => $context->instanceid];
        $userlist->add_from_sql('userid', $sql, $params);
    }
}
