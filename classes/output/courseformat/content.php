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
 * Contains the default content output class.
 *
 * @package   format_drip
 * @copyright 2020-2024 onwards Solin (https://solin.co)
 * @author    Denis (denis@solin.co)
 * @author    Martijn (martijn@solin.nl)
 * @author    Onno (onno@solin.co)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_drip\output\courseformat;

use core_courseformat\output\local\content as content_base;
use course_modinfo;
use renderer_base;

/**
 * Base class to render a course content.
 *
 * @package   format_drip
 * @copyright 2020 onwards Solin (https://solin.co)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content extends content_base {

    /**
     * @var bool Drip format has add section after each topic.
     *
     * The responsible for the buttons is core_courseformat\output\local\content\section.
     */
    protected $hasaddsection = false;

    /**
     * Export this data so it can be used as the context for a mustache template (core/inplace_editable).
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(renderer_base $output) {
        global $PAGE;
        $PAGE->requires->js_call_amd('format_drip/mutations', 'init');
        $PAGE->requires->js_call_amd('format_drip/section', 'init');
        return parent::export_for_template($output);
    }

    /**
     * Export sections array data.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return array data context for a mustache template
     */
    protected function export_sections(\renderer_base $output): array {

        $format = $this->format;
        $course = $format->get_course();
        $modinfo = $this->format->get_modinfo();

        // Generate section list.
        $sections = [];
        $stealthsections = [];
        $numsections = $format->get_last_section_number();
        foreach ($this->get_sections_to_display($modinfo) as $sectionnum => $thissection) {
            // The course/view.php check the section existence but the output can be called
            // from other parts so we need to check it.
            if (!$thissection) {
                throw new \moodle_exception('unknowncoursesection', 'error', course_get_url($course),
                    format_string($course->fullname));
            }

            $section = new $this->sectionclass($format, $thissection);

            if ($sectionnum > $numsections) {
                // Activities inside this section are 'orphaned', this section will be printed as 'stealth' below.
                if (!empty($modinfo->sections[$sectionnum])) {
                    $stealthsections[] = $section->export_for_template($output);
                }
                continue;
            }

            if (!$format->is_section_visible($thissection)) {
                continue;
            }

            $sections[] = $section->export_for_template($output);
        }
        if (!empty($stealthsections)) {
            $sections = array_merge($sections, $stealthsections);
        }
        return $sections;
    }

    /**
     * Return an array of sections to display.
     *
     * This method is used to differentiate between display a specific section
     * or a list of them.
     *
     * @param course_modinfo $modinfo the current course modinfo object
     * @return section_info[] an array of section_info to display
     */
    public function get_sections_to_display(course_modinfo $modinfo): array {
        global $USER;

        $enrolstart = $this->format->get_enrolment_start($USER->id);
        $sections = [];
        $singlesectionid = $this->format->get_sectionid();
        if ($singlesectionid) {
            $sectioninfo = $modinfo->get_section_info_by_id($singlesectionid);
            if (!$this->format->can_access_section($sectioninfo, $enrolstart)) {
                return $sections;
            }
            return [$sectioninfo];
        }

        foreach ($modinfo->get_listed_section_info_all() as $sectionnumber => $thissection) {
            // Always show section 0 & section 1, by default.
            if ($sectionnumber < $this->format->get_drip_start()) {
                $sections[$sectionnumber] = $thissection;
                continue;
            }
            $cancaccessformatsection = $this->format->can_access_section($thissection, $enrolstart);
            // Check if this section is visible for the user based on the drip format settings.
            if (!$cancaccessformatsection && empty($course->showhiddendripsections)) {
                break;
            }
            $sections[$sectionnumber] = $thissection;
        }

        return $sections;
    }

}
