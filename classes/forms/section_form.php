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
 * Form definition for sections.
 * *
 * @package   block_voice
 * @copyright 2021 Michael Vangelovski
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_voice\forms;

defined('MOODLE_INTERNAL') || die();

class section_form extends \moodleform {
    public function definition() {
        global $CFG;

        // Get saved values.
        $data = $this->_customdata ?: array();
        $surveyid = array_key_exists('surveyid', $data) ? $data['surveyid'] : '';
        $sectionid = array_key_exists('sectionid', $data) ? $data['sectionid'] : '';

        // Set up form.
        $mform = $this->_form;
        $mform->addElement('hidden', 'action', 'savesection');
        $mform->setType('action', PARAM_ALPHA);
        $mform->addElement('hidden', 'surveyid', $surveyid);
        $mform->setType('surveyid', PARAM_RAW);
        $mform->addElement('hidden', 'sectionid', $sectionid);
        $mform->setType('sectionid', PARAM_RAW);

        // Add section name input.
        $mform->addElement('text', 'name', get_string('sectionname', 'block_voice'), array('style' => 'width:100%;'));
        $mform->setType('name', PARAM_NOTAGS);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

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