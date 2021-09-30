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
 * Student Voice block configuration form definition
 *
 * @package    block_voice
 * @copyright  2021 Michael de Raadt, Michael Vangelovski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/blocks/voice/lib.php');

use \block_voice\controllers\setup;
use \block_voice\controllers\survey;
use \block_voice\utils;

/**
 * Student Voice block config form class
 *
 * @copyright 2021 Michael de Raadt, Michael Vangelovski
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_voice_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $COURSE, $OUTPUT, $CFG, $DB, $PAGE;

        // Add js to load questions.
        $PAGE->requires->js_call_amd('block_voice/editform', 'init', [
            'instanceid' => $this->block->instance->id,
        ]);
        
        // Add css.
        $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/blocks/voice/voice.css', array('nocache' => rand())));
        $PAGE->add_body_class('voice-editform');

        // Show warning when at least one student has completed the survey.
        if (survey::has_responses($this->block->instance->id)) {
            \core\notification::error(get_string('surveyhasresponses', 'block_voice'));
            $PAGE->add_body_class('voice-has-responses');
            //$mform->addElement('static', '', '', '<div class="alert alert-danger alert-block">Survey already has responses. Changes to configuration are not permitted.</div>');
        }

        $sitecontext = CONTEXT::instance_by_id(CONTEXT_SYSTEM);
        if (has_capability('block/voice:administer', $sitecontext)) {
            $mform->addElement('static', '', '', '<a target="_blank" href="'.$CFG->wwwroot.'/blocks/voice/surveyconfig.php">'.
                get_string('voicesurveyconfig', 'block_voice').'</a>');
        }

        
        $mform->addElement('header', 'general', '');

        // Allow the survey to be opened/closed.
        $openoptions = array(
            'open' => get_string('open', 'block_voice'),
            'closed' => get_string('closed', 'block_voice'),
        );
        $mform->addElement('select', 'config_open', get_string('openlabel', 'block_voice'), $openoptions, 'open');

        // Choose teacher (people who can add this block).
        //$teachers = get_users_by_capability($this->block->context, 'block/voice:addinstance');

        $settings = get_config('block_voice');
        if (empty($settings->surveyroles)) {
            $settings->surveyroles = [3]; // "3" is editing teacher and default.
        }
        $teachers = utils::get_users_by_role_ids($COURSE->id, explode(',', $settings->surveyroles));
        $selectableteachers = array();
        foreach ($teachers as $id => $teacher) {
            $selectableteachers[$id] = fullname($teacher);
        }
        $mform->addElement('select', 'config_teacher', get_string('teacher', 'block_voice'), $selectableteachers);

        // Choose group.
        $groupselected = 0;
        $groupids = array();
        $groupidnums = array();
        $groupingids = array();

        $groups = groups_get_all_groups($COURSE->id);
        $groupings = groups_get_all_groupings($COURSE->id);
        $groupstodisplay['all'] = get_string('allparticipants');
        foreach ($groups as $groupidnum => $groupobject) {
            $groupid = 'group-'.$groupidnum;
            $groupstodisplay[$groupid] = format_string($groupobject->name);
        }
        foreach ($groupings as $groupingidnum => $groupingobject) {
            $groupingid = 'grouping-'.$groupingidnum;
            $groupstodisplay[$groupingid] = format_string($groupingobject->name);
        }
        $mform->addElement('select', 'config_group', get_string('group', 'block_voice'), $groupstodisplay);

        // Choose survey.
        $surveys = setup::get_surveys();
        $selectablesurveys = array();
        foreach ($surveys as $survey) {
            $selectablesurveys[$survey->id] = $survey->name;
        }
        $mform->addElement('select', 'config_survey', get_string('survey', 'block_voice'), $selectablesurveys);

        
        // Set block instance title.
        $mform->addElement('text', 'config_title',
                           get_string('config_title', 'block_voice'), array('optional' => true));
        $mform->setDefault('config_title', get_string('pluginname', 'block_voice'));
        $mform->setType('config_title', PARAM_TEXT);
        $mform->setAdvanced('config_title');

        // Survey sections/questions. Loaded via ajax.
        $mform->addElement('header', 'questionsheader', 'Questions');
        $mform->addElement('html', '<div id="questions"></div>');
        $mform->addElement('hidden', 'config_questionscsv');
        $mform->setType('config_questionscsv', PARAM_TEXT);
        $mform->setExpanded('questionsheader');

    }

    /**
     * Return submitted data.
     *
     * @return object submitted data.
     */
    public function get_data() {
        global $COURSE, $DB;

        $data = parent::get_data();

        if (empty($data)) {
            return $data;
        }
        
        $existing = $DB->get_record('block_voice_teachersurvey', array('blockinstanceid' => $this->block->instance->id));

        // Prevent saving when at least one student has completed the survey.
        if (survey::has_responses($this->block->instance->id)) {
            // Only save title changes.
            $existing->title = $data->config_title;
            $existing->surveyopen = $data->config_open;
            $DB->update_record('block_voice_teachersurvey', $existing);

            // Disallow changing of other data.
            $data->config_group = $existing->surveygroup;
            $data->config_survey = $existing->surveyid;
            $data->config_teacher = $existing->userid;
            $questions = $DB->get_records('block_voice_surveyquestions', array('teachersurveyid' => $existing->id));
            $data->config_questionscsv = implode(',', array_column($questions, 'id'));
            return $data;
        }

        //echo "<pre>"; var_export($data); exit;

        // Save the config to the teachersurvey and surveyquestions tables. These are convenience tables for reporting.
        if ($existing) {
            $DB->delete_records('block_voice_teachersurvey', array('id' => $existing->id));
            $DB->delete_records('block_voice_surveyquestions', array('teachersurveyid' => $existing->id));
        }
        $teachersurvey = new \stdClass();
        $teachersurvey->blockinstanceid = $this->block->instance->id;
        $teachersurvey->title = $data->config_title;
        $teachersurvey->surveyopen = $data->config_open;
        $teachersurvey->surveygroup = $data->config_group;
        $teachersurvey->surveyid = $data->config_survey;
        $teachersurvey->userid = $data->config_teacher;
        $teachersurvey->id = $DB->insert_record('block_voice_teachersurvey', $teachersurvey);
        if ($teachersurvey->id) {
            $questions = explode(',', $data->config_questionscsv);
            foreach($questions as $questionid) {
                $surveyquestion = new \stdClass();
                $surveyquestion->teachersurveyid = $teachersurvey->id ;
                $surveyquestion->questionid = $questionid;
                $DB->insert_record('block_voice_surveyquestions', $surveyquestion);
            }
        }

        return $data;
    }





    /**
     * Set form data.
     *
     * @param array $defaults
     * @return void
     */
    public function set_data($defaults) {
        // Set form data.
        parent::set_data($defaults);
    }










}
