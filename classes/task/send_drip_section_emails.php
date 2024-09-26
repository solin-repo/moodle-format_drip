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
 * Version details
 *
 * @package   format_drip
 * @copyright 2020-2024 onwards Solin (https://solin.co)
 * @author    Denis (denis@solin.co)
 * @author    Martijn (martijn@solin.nl)
 * @author    Onno (onno@solin.co)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_drip\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/format/drip/lib.php');

/**
 * Scheduled task for sending out ('dripping') emails whenever a new section is available.
 *
 * @package   format_drip
 * @copyright 2020-2024 onwards Solin (https://solin.co)
 * @author    Denis (denis@solin.co)
 * @author    Martijn (martijn@solin.nl)
 * @author    Onno (onno@solin.co)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_drip_section_emails extends \core\task\scheduled_task {

    /**
     * Returns the name of the task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('send_drip_section_emails', 'format_drip');
    }

    /**
     * Executes the task to send drip section emails.
     */
    public function execute() {
        global $DB, $SITE;

        // Get the current timestamp in PHP.
        $currenttimestamp = time();

        // SQL query to retrieve users who are due to receive a notification (we skip the sections 0 & 1 by default).
        $sql = "
            SELECT
                ue.id,
                u.id AS userid,
                u.email,
                u.firstname,
                u.lastname,
                u.middlename,
                u.alternatename,
                u.firstnamephonetic,
                u.lastnamephonetic,
                u.username,
                cs.id AS sectionid,
                cs.name AS sectiontitle,
                c.shortname AS shortcoursename,
                c.fullname AS fullcoursename,
                c.id AS courseid,
                ue.timestart AS enrolstart,
                dripinterval.value AS dripinterval,
                COALESCE(dripstart.value, :dripstart1) AS dripstart
            FROM
                {user_enrolments} ue
            JOIN
                {enrol} e ON ue.enrolid = e.id
            JOIN
                {course_sections} cs ON cs.course = e.courseid
            JOIN
                {course} c ON c.id = cs.course
            JOIN
                {user} u ON u.id = ue.userid
            JOIN
                {course_format_options} dripinterval ON dripinterval.courseid = c.id AND dripinterval.name = 'dripinterval'
            LEFT JOIN
                {course_format_options} dripstart ON dripstart.courseid = c.id AND dripstart.name = 'dripstart'
            LEFT JOIN
                {format_drip_email_log} edl ON edl.userid = u.id AND edl.sectionid = cs.id
            WHERE
                e.courseid IN (SELECT id FROM {course} WHERE format = 'drip')
            AND
                cs.section >= COALESCE(dripstart.value, :dripstart2)
            AND
                ue.status = :enrolstatus
            AND
                edl.id IS NULL
            AND
                ue.timestart > 0
            AND
                :currenttimestamp >= (ue.timestart + (dripinterval.value * cs.section * 86400))
            ORDER BY
                u.id, cs.section ASC
        ";

        // Execute the query with the current timestamp and enrolment status.
        $recordset = $DB->get_recordset_sql($sql, [
            'enrolstatus' => ENROL_USER_ACTIVE,
            'currenttimestamp' => $currenttimestamp,
            'dripstart1' => \format_drip::DRIP_START,
            'dripstart2' => \format_drip::DRIP_START,
        ]);

        // Process each record to send email and log the sent notifications.
        foreach ($recordset as $record) {
            // Create a user object with the retrieved name fields.
            $user = (object) [
                'id' => $record->userid,
                'email' => $record->email,
                'firstname' => $record->firstname,
                'lastname' => $record->lastname,
                'middlename' => $record->middlename,
                'alternatename' => $record->alternatename,
                'firstnamephonetic' => $record->firstnamephonetic,
                'lastnamephonetic' => $record->lastnamephonetic,
                'username' => $record->username,
            ];

            // Use fullname() to generate the correct full name format.
            $fullname = fullname($user);

            // Construct course and section URLs.
            $courseurl = new \moodle_url('/course/view.php', ['id' => $record->courseid]);
            $sectionurl = new \moodle_url('/course/section.php', [
                'id' => $record->sectionid,
            ]);

            // Prepare email data.
            $a = (object) [
                'username' => $record->username,
                'firstname' => $record->firstname,
                'lastname' => $record->lastname,
                'fullname' => $fullname,
                'fullcoursename' => $record->fullcoursename,
                'shortcoursename' => $record->shortcoursename,
                'courseurl' => $courseurl->out(),
                'sectiontitle' => $record->sectiontitle,
                'sectionurl' => $sectionurl->out(),
                'sitename' => $SITE->fullname,
            ];

            // Send the email to the user.
            $this->send_email($user, $a);

            // Mark that this user has received the email.
            $this->mark_user_received_email($record->userid, $record->sectionid);
        }
        $recordset->close();
    }

    /**
     * Sends an email to the user about the new section availability.
     *
     * @param object $user - The user object containing user information.
     * @param object $a - The data object for email placeholders.
     */
    protected function send_email($user, $a) {
        $subject = get_string('email-newcontentavailable-subject', 'format_drip', $a);
        $messagehtml = get_string('email-newcontentavailable-html', 'format_drip', $a);
        $messagetext = get_string('email-newcontentavailable-text', 'format_drip', $a);

        email_to_user($user, $a->shortcoursename, $subject, $messagetext, $messagehtml);
    }

    /**
     * Logs the email sent to the user for a specific section.
     *
     * @param int $userid - The ID of the user.
     * @param int $sectionid - The ID of the section.
     */
    protected function mark_user_received_email($userid, $sectionid) {
        global $DB;

        $record = new \stdClass();
        $record->userid = $userid;
        $record->sectionid = $sectionid;
        $record->timecreated = time();

        $DB->insert_record('format_drip_email_log', $record);
    }
}
