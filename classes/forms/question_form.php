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
 * Form definition for questions.
 * *
 * @package   block_voice
 * @copyright 2021 Michael Vangelovski
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_voice\forms;

defined('MOODLE_INTERNAL') || die();

class question_form extends \moodleform {

    public function definition() {
        global $CFG;

        // Get saved values.
        $data = $this->_customdata ?: array();
        $questiontext = array_key_exists('questiontext', $data) ? $data['questiontext'] : '';
        $surveyid = array_key_exists('surveyid', $data) ? $data['surveyid'] : '';
        $sectionid = array_key_exists('sectionid', $data) ? $data['sectionid'] : '';
        $questionid = array_key_exists('questionid', $data) ? $data['questionid'] : '';

        // Set up form.
        $mform = $this->_form;
        $mform->addElement('hidden', 'action', 'savequestion');
        $mform->setType('action', PARAM_ALPHA);
        $mform->addElement('hidden', 'surveyid', $surveyid);
        $mform->setType('surveyid', PARAM_RAW);
        $mform->addElement('hidden', 'sectionid', $sectionid);
        $mform->setType('sectionid', PARAM_RAW);
        $mform->addElement('hidden', 'questionid', $questionid);
        $mform->setType('questionid', PARAM_RAW);

        // Add question name input.
        $mform->addElement('text', 'name', get_string('questionname', 'block_voice'), array('style' => 'width:100%;'));
        $mform->setType('name', PARAM_NOTAGS);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        // Add question text editor.
        $context = \context_system::instance();
        $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'context' => $context, 'elementid' => time());
        $mform->addElement('editor', 'questiontext_editor', get_string('questiontext', 'block_voice'), null, $editoroptions);
        $mform->setType('questiontext_editor', PARAM_CLEANHTML);
        $mform->setDefault('questiontext_editor', array('text' => $questiontext, null));
        $mform->addRule('questiontext_editor', get_string('required'), 'required', null, 'client');

        // Add mandatory control.
        $mandatorycheckbox = array (
            $mform->createElement('advcheckbox', 'mandatory', get_string('mandatorylabel', 'block_voice'), '', [], array(0, 1)),
        );
        $mform->addGroup($mandatorycheckbox, '', get_string('mandatory', 'block_voice'));
        $mform->setDefault('mandatory', 1);

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