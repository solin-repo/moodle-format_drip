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
 * Strings for component 'format_drip', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   format_drip
 * @copyright 2020-2024 onwards Solin (https://solin.co)
 * @author    Denis (denis@solin.co)
 * @author    Martijn (martijn@solin.nl)
 * @author    Onno (onno@solin.co)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addsections'] = 'Add sections';
$string['currentsection'] = 'Current section';
$string['deletesection'] = 'Delete section';
$string['dripdate'] = 'Section drip date';
$string['dripdateelement'] = 'Drip date for this section';
$string['dripdateelement_help'] = 'The date that this section becomes available to the user.';
$string['dripdayselement'] = 'Drip Interval (days)';
$string['dripdayselement_help'] = 'The number of days between the release of each section to the user.';
$string['dripinterval'] = 'Drip Interval (days)';
$string['dripinterval:notanumber'] = 'Please provide a number';
$string['dripsection_info'] = 'This section will open on: {$a}.';
$string['dripstart'] = 'Drip starts at';
$string['dripstartelement'] = 'First section to open';
$string['dripstartelement_help'] = 'The first section to \'drip\' to the user (e.g. 2 means section 1 is always immediately visible and 2 is the first section to be released later)';
$string['driptopicoutline'] = 'Section outline';
$string['editsection'] = 'Edit section';
$string['editsectionname'] = 'Edit section name';
$string['email-newcontentavailable-html'] = '
<p>Hi {$a->firstname},</p>
<p>Great news! 🎉 New content in your course, <strong>{$a->fullcoursename}</strong>, is now live and ready for you to explore:</p>
<p><a href="{$a->sectionlink}" target="_blank"><strong>{$a->sectiontitle}</strong></a></p>
<p>Click on the title above to dive in, discover new insights, and engage with fresh material that will take your learning journey to the next level.</p>
<p>We can\'t wait to see your progress! Head over to <a href="{$a->sectionlink}" target="_blank">{$a->fullcoursename}</a> now and start exploring.</p>
<p>Best regards,<br>
{$a->sitename}</p>
<p class="powered-by">Powered by <a href="https://solin.co" target="_blank">Solin</a></p>';
$string['email-newcontentavailable-html-label'] = 'Email body html';
$string['email-newcontentavailable-html_help'] = 'Html version of the email sent out whenever a new section is available. You can use the following variables:
<ul>
<li>{$a->firstname}</li>
<li>{$a->lastname}</li>
<li>{$a->fullname} - The first name and last name combined</li>
<li>{$a->fullcoursename} - The full name of the course</li>
<li>{$a->shortcoursename} - The short name of the course</li>
<li>{$a->courseurl} - The url pointing to the course (it\'s \'web address\')</li>
<li>{$a->sectiontitle} - The title of the section</li>
<li>{$a->sectionurl} - The url pointing to the section (it\'s \'web address\')</li>
</ul>';
$string['email-newcontentavailable-subject'] = 'Exciting News! New Content Now Available in {$a->shortcoursename}';
$string['email-newcontentavailable-subject-label'] = 'Email subject';
$string['email-newcontentavailable-subject_help'] = 'Subject line of the email sent out whenever a new section is available. You can use the following variables:
<ul>
<li>{$a->firstname}</li>
<li>{$a->lastname}</li>
<li>{$a->fullname} - The first name and last name combined</li>
<li>{$a->fullcoursename} - The full name of the course</li>
<li>{$a->shortcoursename} - The short name of the course</li>
<li>{$a->courseurl} - The url pointing to the course (it\'s \'web address\')</li>
<li>{$a->sectiontitle} - The title of the section</li>
<li>{$a->sectionurl} - The url pointing to the section (it\'s \'web address\')</li>
</ul>';
$string['email-newcontentavailable-text'] = 'Hi {$a->firstname},

Great news! 🎉 New content in your course, {$a->fullcoursename}, is now live and ready for you to explore: {$a->sectiontitle}.

You can access it directly by visiting the following link: {$a->sectionurl}

Dive in to discover new insights and engage with fresh material that will take your learning journey to the next level.

We can\'t wait to see your progress! Head over to {$a->fullcoursename} now and start exploring.

Best regards,
{$a->sitename}
Powered by Solin (https://solin.co)';
$string['email-newcontentavailable-text-label'] = 'Email body plain text';
$string['email-newcontentavailable-text_help'] = 'Plain text version of the email sent out whenever a new section is available. You can use the following variables:
<ul>
<li>{$a->firstname}</li>
<li>{$a->lastname}</li>
<li>{$a->fullname} - The first name and last name combined</li>
<li>{$a->fullcoursename} - The full name of the course</li>
<li>{$a->shortcoursename} - The short name of the course</li>
<li>{$a->courseurl} - The url pointing to the course (it\'s \'web address\')</li>
<li>{$a->sectiontitle} - The title of the section</li>
<li>{$a->sectionurl} - The url pointing to the section (it\'s \'web address\')</li>
</ul>';
$string['hidefromothers'] = 'Hide section';
$string['indentation'] = 'Allow indentation on course page';
$string['indentation_help'] = 'Allow teachers, and other users with the manage activities capability, to indent items on the course page.';
$string['invalid_dripdays_value'] = 'Invalid dripdays value, please fill in a valid number.';
$string['legacysectionname'] = 'Drip section';
$string['newsection'] = 'New section';
$string['newsectionname'] = 'New name for section {$a}';
$string['page-course-view-drip'] = 'Any course main page in drip format';
$string['page-course-view-drip-x'] = 'Any course page in drip format';
$string['plugin_description'] = 'The course is divided into sections that are \'dripped out\' over time at set intervals. Developed and maintained by Solin (https://solin.co)';
$string['pluginname'] = 'Drip format (by Solin)';
$string['privacy:metadata'] = 'The Drip format plugin does not store any personal data.';
$string['section0name'] = 'General';
$string['section_highlight_feedback'] = 'Section {$a->name} highlighted.';
$string['section_unhighlight_feedback'] = 'Highlighting removed from section {$a->name}.';
$string['sectionname'] = 'Section';
$string['sectionname_form'] = 'Drip section {$a} days';
$string['send_drip_section_emails'] = 'Send notifications for newly available drip course sections';
$string['showfromothers'] = 'Show';
$string['showhiddendripsections'] = 'Show unavailable drip sections';
$string['showhiddendripsections_help'] = 'Whether to show the drip sections that are not available to the user yet. Users get to see when the drip section will open.';
