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
 * Student Voice block common configuration and helper functions
 *
 * @package    block_voice
 * @copyright  2021 Michael de Raadt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir.'/formslib.php');

// Global defaults.
const SURVEY_FORMAT_LIKERT = 0;
const SURVEY_FORMAT_THUMBS = 1;
const EYE_OPEN = '<i class="fa fa-eye"></i>';
const EYE_CLOSED = '<i class="fa fa-eye-slash"></i>';
const PLUS = '<i class="fa fa-plus-circle"></i>';
const COG = '<i class="fa fa-cog"></i>';
const TRASH = '<i class="fa fa-trash-o"></i>';
const THUMB = '<i class="fa fa-thumbs-o-up"></i>';
const ELIPSIS = '<i class="fa fa-ellipsis-h"></i>';
const LOCK = '<i class="fa fa-lock"></i>';
const INFO = '<i class="fa  fa-info-circle"></i>';

/**
 * Get all surveys.
 *
 * @param bool hidden controls whether only visible surveys are returned
 * @return array of surveys
 */
function block_voice_get_surveys($hidden = false) {
    global $DB;

    if (!$hidden) {
        $where = "WHERE visible = 1";
    }

    $surveys = $DB->get_records_sql("SELECT * FROM {block_voice_survey} $where ORDER BY seq ASC");

    return $surveys;
}

/**
 * Get sections.
 *
 * @param int surveyid ID for survey containing sections
 * @return array of sections
 */
function block_voice_get_sections($surveyid = null) {
    global $DB;

    $where = '';
    if ($surveyid) {
        $where = "WHERE surveyid = $surveyid";
    }

    $sections = $DB->get_records_sql("SELECT * from {block_voice_section} $where ORDER BY seq ASC");

    return $sections;
}

/**
 * Get survey questions.
 *
 * @param int surveyid ID for survey containing questions
 * @return array of questions
 */
function block_voice_get_questions($surveyid = null) {
    global $DB;

    if ($surveyid) {
        $where = "WHERE surveyid = $surveyid";
    }
    $sections = $DB->get_records_sql("SELECT id from {block_voice_section} $where ORDER BY seq ASC");
    if (empty($sections)) {
        return null;
    }
    $sectionids = implode(',', array_keys($sections));
    $questions = $DB->get_records_sql("SELECT * from {block_voice_question} WHERE sectionid in ($sectionids) ORDER BY seq ASC");

    return $questions;
}

// TODO: Add class and method PHPdoc comments.
class survey_form extends moodleform {

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
        $context = context_system::instance();
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

class section_form extends moodleform {

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

class question_form extends moodleform {

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
        $context = context_system::instance();
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
