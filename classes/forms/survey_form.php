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
 * Form definition for surveys.
 * *
 * @package   block_voice
 * @copyright 2021 Michael Vangelovski
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_voice\forms;

defined('MOODLE_INTERNAL') || die();

class survey_form extends \moodleform {

    public function definition() {
        global $CFG;

        // Get saved values.
        $data = $this->_customdata ?: array();
        $intro = array_key_exists('intro', $data) ? $data['intro'] : '';
        $surveyid = array_key_exists('surveyid', $data) ? $data['surveyid'] : '';

        // Set up form.
        $mform = $this->_form;
        $mform->addElement('hidden', 'action', 'save');
        $mform->setType('action', PARAM_ALPHA);
        $mform->addElement('hidden', 'surveyid', $surveyid);
        $mform->setType('surveyid', PARAM_RAW);

        // Add survey name input.
        $mform->addElement('text', 'name', get_string('name', 'block_voice'), array('style' => 'width:100%;'));
        $mform->setType('name', PARAM_NOTAGS);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        // Add survey intro editor.
        $context = \context_system::instance();
        $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'context' => $context, 'elementid' => time());
        $mform->addElement('editor', 'intro_editor', get_string('introtext', 'block_voice'), null, $editoroptions);
        $mform->setType('intro_editor', PARAM_CLEANHTML);
        $mform->setDefault('intro_editor', array('text' => $intro, null));

        // Add survey format input.
        $formats = array (
            $mform->createElement('radio', 'format', '', get_string('likertscale', 'block_voice'), SURVEY_FORMAT_LIKERT),
            $mform->createElement('radio', 'format', '', get_string('thumbsscale', 'block_voice'), SURVEY_FORMAT_THUMBS),
        );
        $mform->addGroup($formats, 'formatgroup', get_string('surveyformat', 'block_voice'), array(' '), false);
        $mform->setDefault('format', SURVEY_FORMAT_LIKERT);

        // Add visibilty control.
        $visibiltycheckbox = array (
            $mform->createElement('advcheckbox', 'visible', get_string('visible', 'block_voice'), '', array(), array(0, 1)),
        );
        $mform->addGroup($visibiltycheckbox, '', get_string('visibility', 'block_voice'));
        $mform->setDefault('visible', 1);

        // Add standard form buttons.
        $buttonarray = array(
            $mform->createElement('submit', 'submitbutton', get_string('save')),
            $mform->createElement('cancel'),
        );
        $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);
    }

    public function validation($data, $files) {
        return array();
    }
}