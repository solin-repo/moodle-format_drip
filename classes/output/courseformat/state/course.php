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

namespace format_drip\output\courseformat\state;

use core_courseformat\output\local\state\course as base_course;
use core_courseformat\base as course_format;
use course_modinfo;
use stdClass;

/**
 * Contains the ajax update course structure.
 *
 * @package   format_drip
 * @copyright 2024 onwards Solin (https://solin.co)
 * @author    Onno (onno@solin.co)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course extends base_course {

    /**
     * Export this data so it can be used as a state object in the course editor,
     * filtering out sections that the user cannot access.
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): stdClass {
        global $CFG, $USER;

        $format = $this->format;
        $course = $format->get_course();
        $context = \context_course::instance($course->id);
        // State must represent always the most updated version of the course.
        $modinfo = course_modinfo::instance($course);

        $url = new \moodle_url('/course/view.php', ['id' => $course->id]);
        $maxbytes = get_user_max_upload_file_size($context, $CFG->maxbytes, $course->maxbytes);

        $data = (object)[
            'id' => $course->id,
            'numsections' => $format->get_last_section_number(),
            'sectionlist' => [],
            'editmode' => $format->show_editor(),
            'highlighted' => $format->get_section_highlighted_name(),
            'maxsections' => $format->get_max_sections(),
            'baseurl' => $url->out(),
            'statekey' => course_format::session_cache($course),
            'maxbytes' => $maxbytes,
            'maxbytestext' => display_size($maxbytes),
        ];

        // Get all sections.
        $sections = $modinfo->get_section_info_all();
        $enrolstart = $format->get_enrolment_start($USER->id);

        // Filter sections based on user access.
        foreach ($sections as $section) {
            if ($format->is_section_visible($section) && $format->can_access_section($section, $enrolstart)) {
                $data->sectionlist[] = $section->id;
            }
        }

        return $data;
    }
}
