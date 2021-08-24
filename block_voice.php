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
 * Student Voice block definition
 *
 * @package    block_voice
 * @copyright  2021 Michael de Raadt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/blocks/voice/lib.php');

use \block_voice\controllers\survey;

/**
 * Student Voice block class
 *
 * @copyright 2016 Michael de Raadt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_voice extends block_base {

    /**
     * Sets the block title
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_voice');
    }

    /**
     *  we have global config/settings data
     *
     * @return bool
     */
    public function has_config() {
        return true;
    }

    /**
     * Controls the block title based on instance configuration
     *
     * @return bool
     */
    public function specialization() {
        if (isset($this->config->title) && trim($this->config->title) != '') {
            $this->title = format_string($this->config->title);
        }
    }

    /**
     * Controls whether multiple instances of the block are allowed on a page
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * Controls whether the block is configurable
     *
     * @return bool
     */
    public function instance_allow_config() {
        return true;
    }

    /**
     * Defines where the block can be added
     *
     * @return array
     */
    public function applicable_formats() {
        return array(
            'course-view'    => true,
            'site'           => false,
            'mod'            => false,
            'my'             => false,
        );
    }

    /**
     * Creates the blocks main content
     *
     * @return string
     */
    public function get_content() {
        global $USER, $COURSE, $CFG, $OUTPUT, $DB, $PAGE;

        // If content has already been generated, don't waste time generating it again.
        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        if (!$this->config) {
            $this->content->text = get_string('setupfirst', 'block_voice');
            return $this->content;
        }
    
        // Add css.
        $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/blocks/voice/voice.css', array('nocache' => rand())));

        // Teacher view.
        if (has_capability('block/voice:addinstance', $this->context)) {
            // Check if configured yet.
            if (!isset($this->config->teacher)) {
                $this->content->text .= 'Configure to set questions.';
                return $this->content;
            }
            // If survey teacher, show stats.
            if ($this->config->teacher == $USER->id) {
                // TODO: Show progress bar of survey completions.
                // TODO: Link to page showing student completions.
                $this->content->text .= survey::get_block_html_for_teacher($COURSE->id, $this->instance->id, $USER->id);
            } else {
                // If another teacher dont show block, except when editing.
                return null;
            }
        } else {
            // Student view.
            $show = false;
            if ($this->config->group == 'all') {
                $show = true;
            } else if (substr($this->config->group, 0, 9) == 'grouping-') {
                $grouping = (int) substr($this->config->group, 9);
                $members = groups_get_grouping_members($grouping);
                $show = in_array($USER->id, array_keys($members));

            } else if (substr($this->config->group, 0, 6) == 'group-') {
                $group = (int) substr($this->config->group, 6);
                $members = groups_get_members($group);
                $show = in_array($USER->id, array_keys($members));
            }
            if ($show) {
                $this->content->text .= survey::get_block_html_for_student($COURSE->id, $this->instance->id, $USER->id);
            }
        }

        return $this->content;
    }


}
