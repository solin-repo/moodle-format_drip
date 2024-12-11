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
 * Privacy provider tests for the Drip course format.
 *
 * @package   format_drip
 * @copyright 2020-2024 Solin
 * @author    Onno (onno@solin.co)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_drip\privacy;

use core_privacy\local\request\approved_userlist;
use context_course;
use format_drip\privacy\provider;
use stdClass;

/**
 * Unit tests for the Drip course format privacy provider.
 *
 * @covers \format_drip\privacy\provider
 * @group format_drip
 */
final class provider_test extends \core_privacy\tests\provider_testcase {
    /**
     * Test get_metadata().
     *
     * @covers \format_drip\privacy\provider::get_metadata
     */
    public function test_get_metadata(): void {
        // Initialize the metadata collection for the plugin.
        $collection = new \core_privacy\local\metadata\collection('format_drip');
        $collection = provider::get_metadata($collection);

        // Retrieve the items added by the get_metadata method.
        $items = $collection->get_collection();

        // Assert that the collection contains exactly one item.
        $this->assertCount(1, $items);

        // Retrieve the first metadata item (the database table).
        $table = reset($items);

        // Assert that the metadata item is a database table object.
        $this->assertInstanceOf(\core_privacy\local\metadata\types\database_table::class, $table);

        // Verify the table name and metadata summary.
        $this->assertEquals('format_drip_email_log', $table->get_name());
        $this->assertEquals('privacy:metadata:format_drip_email_log', $table->get_summary());

        // Verify the fields metadata for the table.
        $fields = $table->get_privacy_fields();
        $this->assertArrayHasKey('userid', $fields);
        $this->assertArrayHasKey('sectionid', $fields);
        $this->assertArrayHasKey('timecreated', $fields);
    }

    /**
     * Test get_contexts_for_userid().
     *
     * @covers \format_drip\privacy\provider::get_contexts_for_userid
     */
    public function test_get_contexts_for_userid(): void {
        global $DB;

        $this->resetAfterTest(true); // Ensure proper database reset after test.

        // Create a course and user.
        $course = $this->getDataGenerator()->create_course(['format' => 'drip']);
        $user = $this->getDataGenerator()->create_user();

        // Enroll the user in the course.
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        // Get the context for the course.
        $context = context_course::instance($course->id);

        // Insert a record into format_drip_email_log.
        $record = new stdClass();
        $record->userid = $user->id;
        $record->sectionid = $DB->get_field('course_sections', 'id', ['course' => $course->id, 'section' => 1]);
        $record->timecreated = time();
        $DB->insert_record('format_drip_email_log', $record);

        // Call get_contexts_for_userid().
        $contextlist = provider::get_contexts_for_userid($user->id);

        // Verify the context is included.
        $this->assertCount(1, $contextlist->get_contexts());
        $this->assertEquals($context->id, $contextlist->current()->id);
    }

    /**
     * Test delete_data_for_users().
     *
     * @covers \format_drip\privacy\provider::delete_data_for_users
     */
    public function test_delete_data_for_users(): void {
        global $DB;

        $this->resetAfterTest(true); // Ensure proper database reset after test.

        // Create a course and users.
        $course = $this->getDataGenerator()->create_course(['format' => 'drip']);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Enroll users in the course.
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);

        // Insert records into format_drip_email_log.
        $sectionid = $DB->get_field('course_sections', 'id', ['course' => $course->id, 'section' => 1]);
        $DB->insert_record('format_drip_email_log', (object)['userid' => $user1->id, 'sectionid' => $sectionid,
            'timecreated' => time()]);
        $DB->insert_record('format_drip_email_log', (object)['userid' => $user2->id, 'sectionid' => $sectionid,
            'timecreated' => time()]);

        // Prepare the approved userlist.
        $context = context_course::instance($course->id);
        $userlist = new approved_userlist($context, 'format_drip', [$user1->id]);

        // Call delete_data_for_users().
        provider::delete_data_for_users($userlist);

        // Verify that user1's data is deleted.
        $this->assertFalse($DB->record_exists('format_drip_email_log', ['userid' => $user1->id]));

        // Verify that user2's data still exists.
        $this->assertTrue($DB->record_exists('format_drip_email_log', ['userid' => $user2->id]));
    }

    /**
     * Test get_users_in_context().
     *
     * @covers \format_drip\privacy\provider::get_users_in_context
     */
    public function test_get_users_in_context(): void {
        global $DB;

        $this->resetAfterTest(true); // Ensure proper database reset after test.

        // Create a course and users.
        $course = $this->getDataGenerator()->create_course(['format' => 'drip']);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Enroll users in the course.
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);

        // Get the context for the course.
        $context = \context_course::instance($course->id);

        // Create a section.
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 1]);

        // Add a log entry for user1.
        $DB->insert_record('format_drip_email_log', (object)[
            'userid' => $user1->id,
            'sectionid' => $section->id,
            'timecreated' => time(),
        ]);

        // Retrieve users in context.
        $userlist = new \core_privacy\local\request\userlist($context, 'format_drip');
        provider::get_users_in_context($userlist);

        // Retrieve the user IDs from the userlist.
        $userids = $userlist->get_userids();

        // Assertions.
        $this->assertContains((int)$user1->id, array_map('intval', $userids)); // Ensure user1 is included.
        $this->assertNotContains((int)$user2->id, array_map('intval', $userids)); // Ensure user2 is not included.
    }

    /**
     * Test delete_data_for_user().
     *
     * @covers \format_drip\privacy\provider::delete_data_for_user
     */
    public function test_delete_data_for_user(): void {
        global $DB;

        $this->resetAfterTest(true);

        // Create a course and users.
        $course = $this->getDataGenerator()->create_course(['format' => 'drip']);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Enroll users in the course.
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);

        // Create a section and add log entries for users.
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 1]);
        $logdata = [
            ['userid' => $user1->id, 'sectionid' => $section->id, 'timecreated' => time()],
            ['userid' => $user2->id, 'sectionid' => $section->id, 'timecreated' => time()],
        ];
        foreach ($logdata as $record) {
            $DB->insert_record('format_drip_email_log', (object) $record);
        }

        // Context for the course.
        $context = \context_course::instance($course->id);
        $contextlist = new \core_privacy\local\request\approved_contextlist($user1, 'format_drip', [$context->id]);

        // Call delete_data_for_user().
        provider::delete_data_for_user($contextlist);

        // Assert that data for user1 is deleted in this context.
        $this->assertFalse($DB->record_exists('format_drip_email_log', [
            'userid' => $user1->id,
            'sectionid' => $section->id,
        ]));

        // Assert that data for user2 still exists.
        $this->assertTrue($DB->record_exists('format_drip_email_log', [
            'userid' => $user2->id,
            'sectionid' => $section->id,
        ]));
    }

    /**
     * Test delete_data_for_all_users_in_context().
     *
     * @covers \format_drip\privacy\provider::delete_data_for_all_users_in_context
     */
    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;

        $this->resetAfterTest(true);

        // Create a course and users.
        $course = $this->getDataGenerator()->create_course(['format' => 'drip']);
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Enroll users in the course.
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);

        // Create a section and add log entries for users.
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 1]);
        $logdata = [
            ['userid' => $user1->id, 'sectionid' => $section->id, 'timecreated' => time()],
            ['userid' => $user2->id, 'sectionid' => $section->id, 'timecreated' => time()],
        ];
        foreach ($logdata as $record) {
            $DB->insert_record('format_drip_email_log', (object) $record);
        }

        // Context for the course.
        $context = \context_course::instance($course->id);

        // Call delete_data_for_all_users_in_context().
        provider::delete_data_for_all_users_in_context($context);

        // Assert that no data remains for the section.
        $this->assertFalse($DB->record_exists('format_drip_email_log', ['sectionid' => $section->id]));
    }
}
