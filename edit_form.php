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
 * @copyright  2021 Michael de Raadt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/blocks/voice/lib.php');

/**
 * Student Voice block config form class
 *
 * @copyright 2021 Michael de Raadt, Michael Vangelovski
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_voice_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $COURSE, $OUTPUT, $CFG, $DB;

        // TODO: Lock form controls when at least one student has completed the survey.

        $sitecontext = CONTEXT::instance_by_id(CONTEXT_SYSTEM);
        if (has_capability('block/voice:administer', $sitecontext)) {
            $mform->addElement('static', '', '', '<a target="_blank" href="'.$CFG->wwwroot.'/blocks/voice/surveyconfig.php">'.
                get_string('voicesurveyconfig', 'block_voice').'</a>');
        }

        // Set block instance title.
        $mform->addElement('text', 'config_title',
                           get_string('config_title', 'block_voice'));
        $mform->setDefault('config_title', get_string('pluginname', 'block_voice'));
        $mform->setType('config_title', PARAM_TEXT);

        // Allow the survey to be opened/closed.
        $openoptions = array(
            'open' => get_string('open', 'block_voice'),
            'closed' => get_string('closed', 'block_voice'),
        );
        $mform->addElement('select', 'config_open', get_string('openlabel', 'block_voice'), $openoptions, 'open');

        // Choose teacher (people who can add this block).
        $teachers = get_users_by_capability($this->block->context, 'block/voice:addinstance');
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
        // TODO: Update the questions form when user changes the survey.
        $surveys = block_voice_get_surveys();
        $selectablesurveys = array();
        foreach ($surveys as $id => $survey) {
            $selectablesurveys[$id] = $survey->name;
        }
        $mform->addElement('select', 'config_survey', get_string('survey', 'block_voice'), $selectablesurveys);

        //echo "<pre>"; var_export($this->block->config); exit;

        // Get survey sections/questions.
        if (empty($this->block->config->survey)) {
            return;
        }

        $surveyid = $this->block->config->survey;
        $sections = block_voice_get_sections($surveyid);
        $questions = block_voice_get_questions($surveyid);

        foreach ($sections as $sectionid => $section) {
            // Add section heading.
            $mform->addElement('header', 'configheader', format_string($section->name));

            // Show questions.
            foreach ($questions as $id => $question) {
                if ($question->sectionid == $section->id) {
                    $mform->addElement('advcheckbox', 'question_' . $question->id, format_string($question->name), '',
                        $question->mandatory ? array('disabled' => 'true') : '', array(0, 1));
                    if ($question->mandatory) {
                        $mform->setDefault('question_' . $question->id, 1);
                    }
                }
            }
        }
    }






    /**
     * Return submitted data.
     *
     * @return object submitted data.
     */
    public function get_data() {
        $data = parent::get_data();

        if (empty($data)) {
            return $data;
        }

        echo "<pre>"; var_export($data); exit;

        $questions = array();
        foreach((array) $data as $field => $selected) {
            if (preg_match('/^(question_)(\d)$/', $field, $matches)) {
                if ($selected) {
                    $questions[] = $matches[2]; // Question id.
                }
            }
        }
        
        // Create a teachersurvey.
        

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
