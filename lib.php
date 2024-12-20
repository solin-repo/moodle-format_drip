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
 * This file contains main class for Drip course format.
 *
 * @package   format_drip
 * @copyright 2020-2024 onwards Solin (https://solin.co)
 * @author    Denis (denis@solin.co)
 * @author    Martijn (martijn@solin.nl)
 * @author    Onno (onno@solin.co)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot. '/course/format/lib.php');

/**
 * Main class for the Drip course format.
 *
 * @package   format_drip
 * @copyright 2020-2024 onwards Solin (https://solin.co)
 * @author    Denis (denis@solin.co)
 * @author    Martijn (martijn@solin.nl)
 * @author    Onno (onno@solin.co)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_drip extends core_courseformat\base {

    /** @var int Default interval (in days) between each 'drip' (opening of section) */
    const DRIP_INTERVAL = 7;

    /** @var int Default section to start dripping (0 & 1 are always visible) */
    const DRIP_START = 2;

    /**
     * Returns true if this course format uses sections.
     *
     * @return bool
     */
    public function uses_sections() {
        return true;
    }

    /**
     * Returns true if this course format uses the course index.
     *
     * @return bool
     */
    public function uses_course_index() {
        return true;
    }

    /**
     * Returns true if this course format uses indentation.
     *
     * @return bool
     */
    public function uses_indentation(): bool {
        return (get_config('format_drip', 'indentation')) ? true : false;
    }

    /**
     * Returns the display name of the given section that the course prefers.
     *
     * Use section name is specified by user. Otherwise use default ("Drip #").
     *
     * @param int|stdClass $section Section object from database or just field section.section
     * @return string Display name that the course format prefers, e.g. "Drip 2"
     */
    public function get_section_name($section) {
        $section = $this->get_section($section);
        if ((string)$section->name !== '') {
            return format_string($section->name, true,
                ['context' => context_course::instance($this->courseid)]);
        } else {
            return $this->get_default_section_name($section);
        }
    }

    /**
     * Returns the default section name for the drip course format.
     *
     * If the section number is 0, it will use the string with key = section0name from the course format's lang file.
     * If the section number is not 0, it will consistently return the name 'newsection', disregarding the specific section number.
     *
     * @param int|stdClass $section Section object from database or just field course_sections section
     * @return string The default value for the section name.
     */
    public function get_default_section_name($section) {
        $section = $this->get_section($section);
        if ($section->sectionnum == 0) {
            return get_string('section0name', 'format_drip');
        }

        return get_string('newsection', 'format_drip');
    }

    /**
     * Generate the title for this section page.
     *
     * @return string the page title
     */
    public function page_title(): string {
        return get_string('sectionoutline');
    }

    /**
     * The URL to use for the specified course (with section).
     *
     * @param int|stdClass $section Section object from database or just field course_sections.section
     *     if omitted the course view page is returned
     * @param array $options options for view URL. At the moment core uses:
     *     'navigation' (bool) if true and section not empty, the function returns section page; otherwise, it returns course page.
     *     'sr' (int) used by course formats to specify to which section to return
     * @return null|moodle_url
     */
    public function get_view_url($section, $options = []) {
        $course = $this->get_course();
        if (array_key_exists('sr', $options) && !is_null($options['sr'])) {
            $sectionno = $options['sr'];
        } else if (is_object($section)) {
            $sectionno = $section->section;
        } else {
            $sectionno = $section;
        }
        if ((!empty($options['navigation']) || array_key_exists('sr', $options)) && $sectionno !== null) {
            // Display section on separate page.
            $sectioninfo = $this->get_section($sectionno);
            return new moodle_url('/course/section.php', ['id' => $sectioninfo->id]);
        }

        return new moodle_url('/course/view.php', ['id' => $course->id]);
    }

    /**
     * Returns the information about the ajax support in the given source format.
     *
     * The returned object's property (boolean)capable indicates that
     * the course format supports Moodle course ajax features.
     *
     * @return stdClass
     */
    public function supports_ajax() {
        $ajaxsupport = new stdClass();
        $ajaxsupport->capable = true;
        return $ajaxsupport;
    }

    /**
     * Returns true if this course format supports components.
     *
     * @return bool
     */
    public function supports_components() {
        return true;
    }

    /**
     * Loads all of the course sections into the navigation.
     *
     * @param global_navigation $navigation
     * @param navigation_node $node The course node within the navigation
     * @return void
     */
    public function extend_course_navigation($navigation, navigation_node $node) {
        global $PAGE;
        // If section is specified in course/view.php, make sure it is expanded in navigation.
        if ($navigation->includesectionnum === false) {
            $selectedsection = optional_param('section', null, PARAM_INT);
            if ($selectedsection !== null && (!defined('AJAX_SCRIPT') || AJAX_SCRIPT == '0') &&
                    $PAGE->url->compare(new moodle_url('/course/view.php'), URL_MATCH_BASE)) {
                $navigation->includesectionnum = $selectedsection;
            }
        }

        // Check if there are callbacks to extend course navigation.
        parent::extend_course_navigation($navigation, $node);

        // We want to remove the general section if it is empty.
        $modinfo = get_fast_modinfo($this->get_course());
        $sections = $modinfo->get_sections();
        if (!isset($sections[0])) {
            // The general section is empty to find the navigation node for it we need to get its ID.
            $section = $modinfo->get_section_info(0);
            $generalsection = $node->get($section->id, navigation_node::TYPE_SECTION);
            if ($generalsection) {
                // We found the node - now remove it.
                $generalsection->remove();
            }
        }
    }

    /**
     * Custom action after section has been moved in AJAX mode.
     *
     * Used in course/rest.php
     *
     * @return array This will be passed in ajax respose
     */
    public function ajax_section_move() {
        global $PAGE;
        $titles = [];
        $course = $this->get_course();
        $modinfo = get_fast_modinfo($course);
        $renderer = $this->get_renderer($PAGE);
        if ($renderer && ($sections = $modinfo->get_section_info_all())) {
            foreach ($sections as $number => $section) {
                $titles[$number] = $renderer->section_title($section, $course);
            }
        }
        return ['sectiontitles' => $titles, 'action' => 'move'];
    }

    /**
     * Returns the list of blocks to be automatically added for the newly created course.
     *
     * @return array of default blocks, must contain two keys BLOCK_POS_LEFT and BLOCK_POS_RIGHT
     *     each of values is an array of block names (for left and right side columns)
     */
    public function get_default_blocks() {
        return [
            BLOCK_POS_LEFT => [],
            BLOCK_POS_RIGHT => [],
        ];
    }

    /**
     * Definitions of the additional options that this course format uses for course.
     *
     * Drip format uses the following options:
     * - coursedisplay
     * - hiddensections
     *
     * @param bool $foreditform
     * @return array of options
     */
    public function course_format_options($foreditform = false) {
        static $courseformatoptions = false;
        if ($courseformatoptions === false) {
            $courseconfig = get_config('moodlecourse');
            $courseformatoptions = [
                'coursedisplay' => [
                    'default' => $courseconfig->coursedisplay,
                    'type' => PARAM_INT,
                ],
                'dripinterval' => [
                    'type' => PARAM_RAW,
                    'default' => self::DRIP_INTERVAL,
                ],
                'dripstart' => [
                    'type' => PARAM_RAW,
                    'default' => self::DRIP_START,
                ],
                'email-newcontentavailable-subject' => [
                    'type' => PARAM_RAW,
                    'default' => new lang_string('email-newcontentavailable-subject', 'format_drip'),
                ],
                'email-newcontentavailable-text' => [
                    'type' => PARAM_RAW,
                    'default' => new lang_string('email-newcontentavailable-text', 'format_drip'),
                ],
                'email-newcontentavailable-html' => [
                    'type' => PARAM_RAW,
                    'default' => new lang_string('email-newcontentavailable-html', 'format_drip'),
                ],
            ];
        }
        if ($foreditform && !isset($courseformatoptions['coursedisplay']['label'])) {
            $courseformatoptionsedit = [
                'coursedisplay' => [
                    'label' => new lang_string('coursedisplay'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        [
                            COURSE_DISPLAY_SINGLEPAGE => new lang_string('coursedisplay_single'),
                            COURSE_DISPLAY_MULTIPAGE => new lang_string('coursedisplay_multi'),
                        ],
                    ],
                    'help' => 'coursedisplay',
                    'help_component' => 'moodle',
                ],
                'dripinterval' => [
                    'label' => new lang_string('dripinterval', 'format_drip'),
                    'element_type' => 'text',
                    'help' => 'dripdayselement',
                    'help_component' => 'format_drip',
                    'name' => 'dripinterval',
                ],
                'dripstart' => [
                    'label' => new lang_string('dripstart', 'format_drip'),
                    'element_type' => 'text',
                    'help' => 'dripstartelement',
                    'help_component' => 'format_drip',
                    'name' => 'dripstart',
                ],
                'email-newcontentavailable-subject' => [
                    'label' => new lang_string('email-newcontentavailable-subject-label', 'format_drip'),
                    'element_type' => 'text',
                    'element_attributes' => [['size' => 60]],
                    'help' => 'email-newcontentavailable-subject',
                    'help_component' => 'format_drip',
                    'name' => 'email-newcontentavailable-subject',
                ],
                'email-newcontentavailable-text' => [
                    'label' => new lang_string('email-newcontentavailable-text-label', 'format_drip'),
                    'element_type' => 'textarea',
                    'element_attributes' => [
                        [
                            'rows' => 10,
                            'cols' => 60,
                        ],
                    ],
                    'help' => 'email-newcontentavailable-text',
                    'help_component' => 'format_drip',
                    'name' => 'email-newcontentavailable-text',
                ],
                'email-newcontentavailable-html' => [
                    'label' => new lang_string('email-newcontentavailable-html-label', 'format_drip'),
                    'element_type' => 'textarea',
                    'element_attributes' => [
                        [
                            'rows' => 10,
                            'cols' => 60,
                        ],
                    ],
                    'help' => 'email-newcontentavailable-html',
                    'help_component' => 'format_drip',
                    'name' => 'email-newcontentavailable-html',
                ],
            ];
            $courseformatoptions = array_merge_recursive($courseformatoptions, $courseformatoptionsedit);
        }
        return $courseformatoptions;
    }

    /**
     * Adds format options elements to the course/section edit form.
     *
     * This function is called from {@link course_edit_form::definition_after_data()}.
     *
     * @param MoodleQuickForm $mform form the elements are added to.
     * @param bool $forsection 'true' if this is a section edit form, 'false' if this is course edit form.
     * @return array array of references to the added form elements.
     */
    public function create_edit_form_elements(&$mform, $forsection = false) {
        global $COURSE;
        $elements = parent::create_edit_form_elements($mform, $forsection);

        if (!$forsection && (empty($COURSE->id) || $COURSE->id == SITEID)) {
            // Add "numsections" element to the create course form - it will force new course to be prepopulated
            // with empty sections.
            // The "Number of sections" option is no longer available when editing course, instead teachers should
            // delete and add sections when needed.
            $courseconfig = get_config('moodlecourse');
            $max = (int)$courseconfig->maxsections;
            $element = $mform->addElement('select', 'numsections', get_string('numberweeks'), range(0, $max ?: 52));
            $mform->setType('numsections', PARAM_INT);
            if (is_null($mform->getElementValue('numsections'))) {
                $mform->setDefault('numsections', $courseconfig->numsections);
            }
            array_unshift($elements, $element);
        }

        return $elements;
    }

    /**
     * Updates format options for a course.
     *
     * In case if course format was changed to 'drip', we try to copy options
     * 'coursedisplay' and 'hiddensections' from the previous format.
     *
     * @param stdClass|array $data return value from {@link moodleform::get_data()} or array with data
     * @param stdClass $oldcourse if this function is called from {@link update_course()}
     *     this object contains information about the course before update
     * @return bool whether there were any changes to the options values
     */
    public function update_course_format_options($data, $oldcourse = null) {
        $data = (array)$data;
        if ($oldcourse !== null) {
            $oldcourse = (array)$oldcourse;
            $options = $this->course_format_options();
            foreach ($options as $key => $unused) {
                if (!array_key_exists($key, $data)) {
                    if (array_key_exists($key, $oldcourse)) {
                        $data[$key] = $oldcourse[$key];
                    }
                }
            }
        }
        return $this->update_format_options($data);
    }

    /**
     * Whether this format allows to delete sections.
     *
     * Do not call this function directly, instead use {@link course_can_delete_section()}
     *
     * @param int|stdClass|section_info $section
     * @return bool
     */
    public function can_delete_section($section) {
        return true;
    }

    /**
     * Indicates whether the course format supports the creation of a news forum.
     *
     * @return bool
     */
    public function supports_news() {
        return true;
    }

    /**
     * Returns whether this course format allows the activity to
     * have "triple visibility state" - visible always, hidden on course page but available, hidden.
     *
     * @param stdClass|cm_info $cm course module (may be null if we are displaying a form for adding a module)
     * @param stdClass|section_info $section section where this module is located or will be added to
     * @return bool
     */
    public function allow_stealth_module_visibility($cm, $section) {
        // Allow the third visibility state inside visible sections or in section 0.
        return !$section->section || $section->visible;
    }

    /**
     * Callback used in WS core_course_edit_section when teacher performs an AJAX action on a section (show/hide).
     *
     * Access to the course is already validated in the WS but the callback has to make sure
     * that particular action is allowed by checking capabilities
     *
     * Course formats should register.
     *
     * @param section_info|stdClass $section
     * @param string $action
     * @param int $sr
     * @return null|array any data for the Javascript post-processor (must be json-encodeable)
     */
    public function section_action($section, $action, $sr) {
        global $PAGE;

        if ($section->section && ($action === 'setmarker' || $action === 'removemarker')) {
            // Format 'drip' allows to set and remove markers in addition to common section actions.
            require_capability('moodle/course:setcurrentsection', context_course::instance($this->courseid));
            course_set_marker($this->courseid, ($action === 'setmarker') ? $section->section : 0);
            return null;
        }

        // For show/hide actions call the parent method and return the new content for .section_availability element.
        $rv = parent::section_action($section, $action, $sr);
        $renderer = $PAGE->get_renderer('format_drip');

        if (!($section instanceof section_info)) {
            $modinfo = course_modinfo::instance($this->courseid);
            $section = $modinfo->get_section_info($section->section);
        }
        $elementclass = $this->get_output_classname('content\\section\\availability');
        $availability = new $elementclass($this, $section);

        $rv['section_availability'] = $renderer->render($availability);
        return $rv;
    }

    /**
     * Return the plugin configs for external functions.
     *
     * @return array the list of configuration settings
     * @since Moodle 3.5
     */
    public function get_config_for_external() {
        // Return everything (nothing to hide).
        $formatoptions = $this->get_format_options();
        $formatoptions['indentation'] = get_config('format_drip', 'indentation');
        return $formatoptions;
    }

    /**
     * Get the required javascript files for the course format.
     *
     * @return array The list of javascript files required by the course format.
     */
    public function get_required_jsfiles(): array {
        return [];
    }

    /**
     * Checks if the current user can access the section.
     *
     * @param object $section - The section being accessed.
     * @param int $enrolstart - The start date of the enrolment as a Unix timestamp.
     * @return bool - Whether the user can access the section.
     */
    public function can_access_section($section, $enrolstart) {
        global $PAGE, $USER;

        // Site admin can always access the section.
        if (is_siteadmin()) {
            return true;
        }

        $context = context_course::instance($this->course->id);

        // Users with editing capabilities can always access the section.
        if ($PAGE->user_is_editing() && has_capability('moodle/course:update', $context)) {
            return true;
        }

        // Ensure section number is valid.
        if (!isset($section->section) || !is_numeric($section->section)) {
            return false; // Invalid section number, access denied.
        }

        // By default, sections 0 & 1 are always visible.
        if ($section->section < $this->get_drip_start()) {
            return true;
        }

        // Retrieve the drip interval from format options or fallback to the default class constant.
        $formatoptions = $this->get_format_options();
        $dripinterval = $formatoptions['dripinterval'] ?? self::DRIP_INTERVAL;

        // Use the user's time zone if available, otherwise fall back to Moodle's default time zone.
        $usertimezone = $USER->timezone ?? core_date::get_server_timezone()->getName();

        // Get the user's timezone or fallback to Moodle's default server timezone.
        try {
            $usertimezoneobj = new \DateTimeZone($usertimezone); // This is already an object.
        } catch (\Exception $e) {
            // Create an object from the server timezone string.
            $usertimezoneobj = new \DateTimeZone(core_date::get_server_timezone());
        }

        $enrolstartdatetime = new \DateTime('@' . $enrolstart); // Create from Unix timestamp.
        $enrolstartdatetime->setTimezone($usertimezoneobj);
        $enrolstartdatetime->setTime(0, 0); // Round down to local midnight.

        // Calculate the section's open time based on the local midnight of enrolstart.
        $opentime = clone $enrolstartdatetime;
        $opentime->modify('+' . (($section->section - ($this->get_drip_start() - 1)) * $dripinterval) . ' days');

        // Get the current time in the user's local time zone.
        $currenttime = new \DateTime('now', $usertimezoneobj);

        // Allow access if the current local time is past the calculated open time.
        return $currenttime >= $opentime;
    }

    /**
     * Get the start date of the enrolment for the specified user.
     *
     * @param int $userid The ID of the user whose enrolment start date is being retrieved.
     * @return int The timestamp of the start date.
     */
    public function get_enrolment_start($userid) {
        global $DB;

        $sql = "SELECT ue.timestart
                FROM {user_enrolments} ue
                JOIN {enrol} e ON ue.enrolid = e.id
                WHERE e.courseid = :courseid
                AND ue.userid = :userid
                AND ue.status = :enrolstatus
                ORDER BY ue.timestart ASC
                LIMIT 1";
        $params = ['courseid' => $this->course->id, 'userid' => $userid, 'enrolstatus' => ENROL_USER_ACTIVE];

        return $DB->get_field_sql($sql, $params);
    }

    /**
     * Get the drip starting section
     *
     * @return int The number of the starting section
     */
    public function get_drip_start() {
        // Retrieve the drip starting section from format options or fallback to the default class constant.
        $formatoptions = $this->get_format_options();
        if (isset($formatoptions['dripstart'])) {
            return $formatoptions['dripstart'];
        }
        return self::DRIP_START;
    }
}

/**
 * Implements callback inplace_editable() allowing to edit values in-place.
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return inplace_editable
 */
function format_drip_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
            [$itemid, 'drip'], MUST_EXIST);
        return course_get_format($section->course)->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
}

/**
 * Serves the CSS for the drip course format.
 *
 * @param moodle_page $page The Moodle page object.
 */
function format_drip_css(moodle_page $page) {
    $page->requires->css('/course/format/drip/styles.css');
}
