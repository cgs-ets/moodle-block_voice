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
 * Student Voice survey configuration page
 *
 * @package    block_voice
 * @copyright  2021 Michael de Raadt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_voice\utils;
use block_voice\forms\survey_form;
use block_voice\forms\section_form;
use block_voice\forms\question_form;

// Include required files.
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/blocks/voice/lib.php');
require_once($CFG->libdir.'/tablelib.php');

$context = context_system::instance();

// Check user is logged in and capable of accessing the survey config.
require_login();
require_capability('block/voice:administer', $context);

// Determine course and context.
$courseid = 1;
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$action = optional_param('action', '', PARAM_ALPHANUMEXT); // Which page to show.
$buttonattributes = array('class' => 'btn btn-primary', 'style' => 'margin: 0 0 10px 5px;');

// Set up page parameters.
$surveyconfigurl = new moodle_url('/blocks/voice/surveyconfig.php', array());
$PAGE->set_course($course);
$PAGE->set_url($surveyconfigurl);
$PAGE->set_context($context);
$title = get_string('voicesurveyconfig', 'block_voice');
$PAGE->set_title($title);
$PAGE->set_heading($title);
if ($action == 'questions') {
    $PAGE->navbar->add($title, $surveyconfigurl);
    $PAGE->navbar->add(get_string('questions', 'block_voice'));
} else {
    $PAGE->navbar->add($title);
}
$PAGE->set_pagelayout('report');

// Start page output.
$output = $OUTPUT->container_start('survey_config');

// Add question.
if ($action == 'addquestion') {
    $sectionid = required_param('sectionid', PARAM_INT);
    $surveyid = required_param('surveyid', PARAM_INT);
    $output .= $OUTPUT->heading(get_string('addquestion', 'block_voice'), 2);
    $mform = new question_form(null, array('sectionid' => $sectionid, 'surveyid' => $surveyid));
    $output .= $mform->render();
}

// Delete question.
if ($action == 'deletequestion') {
    $sectionid = required_param('sectionid', PARAM_INT);
    $surveyid = required_param('surveyid', PARAM_INT);
    $questionid = required_param('questionid', PARAM_INT);
    $params = array('sectionid' => $sectionid);
    $questions = $DB->get_records_sql('SELECT * FROM {block_voice_question} WHERE sectionid = :sectionid', $params);
    $success = $DB->delete_records('block_voice_question', array('id' => $questionid));
    if ($success) {
        foreach ($questions as $id => $question) {
            if ($question->seq > $questions[$questionid]->seq) {
                $question->seq = $question->seq - 1;
                $DB->update_record('block_voice_question', $question);
            }
        }
        // Redirect back to the questions form.
        $surveyconfigurl->param('action', 'questions');
        $surveyconfigurl->param('surveyid', $surveyid);
        utils::redirect_success($surveyconfigurl, get_string('questiondeletesuccess', 'block_voice', $questions[$questionid]->name));
    }
}

// Edit question.
if ($action == 'editquestion') {
    $sectionid = required_param('sectionid', PARAM_INT);
    $surveyid = required_param('surveyid', PARAM_INT);
    $questionid = required_param('questionid', PARAM_INT);
    $params = array('questionid' => $questionid);
    $question = $DB->get_record_sql('SELECT * FROM {block_voice_question} WHERE id = :questionid', $params);
    if ($question) {
        $output .= $OUTPUT->heading(get_string('edit', 'block_voice'), 2);
        $mform = new question_form(null, array('sectionid' => $sectionid, 'surveyid' => $surveyid, 'questionid' => $questionid,
            'questiontext' => $question->questiontext));
        $mform->set_data($question);
        $output .= $mform->render();
    }
}

// Save question.
if ($action == 'savequestion') {
    // Set up URL for redirect back to the questions form.
    $surveyid = required_param('surveyid', PARAM_INT);
    $surveyconfigurl->param('surveyid', $surveyid);
    $surveyconfigurl->param('action', 'questions');

    // Get form data.
    $mform = new question_form();
    $data = $mform->get_data();

    if ($mform->is_cancelled()) {
        redirect($surveyconfigurl->out());
    }

    // Save data.
    $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'context' => $context);
    $data = file_postupdate_standard_editor($data, 'questiontext', $editoroptions, $context);
    if ($data->questionid != '') {
        $data->id = (int) $data->questionid;
        $success = $DB->update_record('block_voice_question', $data);
    } else {
        $params = array('sectionid' => $data->sectionid);
        $questions = $DB->get_records_sql('SELECT * FROM {block_voice_question} WHERE sectionid = :sectionid', $params);
        $data->seq = count($questions) + 1;
        $success = $DB->insert_record('block_voice_question', $data);
    }
    if ($success) {
        utils::redirect_success($surveyconfigurl, get_string('savesuccess', 'block_voice'));
    }
}

// Add section.
if ($action == 'addsection') {
    // Required params for this action.
    $surveyid = required_param('surveyid', PARAM_INT);
    // Render the form.
    $output .= $OUTPUT->heading(get_string('addsection', 'block_voice'), 2);
    $mform = new section_form(null, array('surveyid' => $surveyid));
    $output .= $mform->render();
}

// Delete section.
if ($action == 'deletesection') {
    $surveyid = required_param('surveyid', PARAM_INT);
    $sectionid = required_param('sectionid', PARAM_INT);
    $params = array('surveyid' => $surveyid);
    $sections = $DB->get_records_sql('SELECT * FROM {block_voice_section} WHERE surveyid = :surveyid', $params);
    $success = $DB->delete_records('block_voice_section', array('id' => $sectionid));
    $success = $success && $DB->delete_records('block_voice_question', array('sectionid' => $sectionid));
    if ($success) {
        foreach ($sections as $id => $section) {
            if ($section->seq > $sections[$sectionid]->seq) {
                $section->seq = $section->seq - 1;
                $DB->update_record('block_voice_section', $section);
            }
        }
        // Redirect back to the questions form.
        $surveyconfigurl->param('action', 'questions');
        $surveyconfigurl->param('surveyid', $surveyid);
        utils::redirect_success($surveyconfigurl, get_string('sectiondeletesuccess', 'block_voice', $sections[$sectionid]->name));
    }
}

// Edit section.
if ($action == 'editsection') {
    $surveyid = required_param('surveyid', PARAM_INT);
    $sectionid = required_param('sectionid', PARAM_INT);
    $params = array('sectionid' => $sectionid);
    $section = $DB->get_record_sql('SELECT * FROM {block_voice_section} WHERE id = :sectionid', $params);
    $output .= $OUTPUT->heading(get_string('edit', 'block_voice'), 2);
    if ($section && $surveyid) {
        $mform = new section_form(null, array('sectionid' => $sectionid, 'surveyid' => $surveyid));
        $mform->set_data($section);
        $output .= $mform->render();
    }
}

// Save section.
if ($action == 'savesection') {
    // Set up URL for redirect back to the questions form.
    $surveyid = required_param('surveyid', PARAM_INT);
    $surveyconfigurl->param('surveyid', $surveyid);
    $surveyconfigurl->param('action', 'questions');

    // Get form data.
    $mform = new section_form();
    $data = $mform->get_data();

    if ($mform->is_cancelled()) {
        redirect($surveyconfigurl->out());
    }

    // Save data.
    if ($data->sectionid != '') {
        $data->id = (int) $data->sectionid;
        $success = $DB->update_record('block_voice_section', $data);
    } else {
        $params = array('surveyid' => $data->surveyid);
        $sections = $DB->get_records_sql('SELECT * FROM {block_voice_section} WHERE surveyid = :surveyid', $params);
        $data->seq = count($sections) + 1;
        $success = $DB->insert_record('block_voice_section', $data);
    }
    if ($success) {
        utils::redirect_success($surveyconfigurl, get_string('savesuccess', 'block_voice'));
    }
}

// Add / Edit sections and questions.
if ($action == 'questions') {
    $output .= $OUTPUT->heading(get_string('questions', 'block_voice'), 2);
    $output .= $OUTPUT->container(INFO . ' ' . get_string('sectionnote', 'block_voice') . ' ' .
        get_string('questionnote', 'block_voice'), 'info');

    // Get data.
    $surveyid = required_param('surveyid', PARAM_INT);
    $sections = $DB->get_records_sql('SELECT * FROM {block_voice_section} WHERE surveyid = :surveyid ORDER BY seq ASC',
        array('surveyid' => $surveyid));
    if ($sections) {
        $sectionids = array();
        foreach ($sections as $id => $section) {
            $sectionids[] = $id;
        }
        $sectionids = implode(',', $sectionids);
        $questions = $DB->get_records_sql("SELECT * FROM {block_voice_question} WHERE sectionid in ($sectionids) ORDER BY seq ASC");
    } else {
        $questions = array();
    }
    $url = clone($PAGE->url);
    $rowbuttonattributes = $buttonattributes;
    $rowbuttonattributes['style'] = 'margin: 0 0 0 5px;';

    // Show list of sections.
    if ($sections) {
        foreach ($sections as $sectionid => $section) {
            $url->param('surveyid', $surveyid);
            $url->param('sectionid', $sectionid);
            $url->param('action', 'deletesection');
            $deletelink = html_writer::link($url, TRASH);
            $url->param('action', 'editsection');
            $editlink = html_writer::link($url, COG);
            $output .= $OUTPUT->heading(format_string($section->name) . ' ' . $deletelink . ' ' . $editlink, 3);

            // Show questions within this section.
            $table = new html_table();
            $table->id = 'surveytable';
            $table->attributes = array('class' => 'table');
            foreach ($questions as $questionid => $question) {
                if ($question->sectionid == $sectionid) {
                    $mandatory = $question->mandatory ? LOCK : '';
                    $url = clone($PAGE->url);
                    $url->param('surveyid', $surveyid);
                    $url->param('sectionid', $question->sectionid);
                    $url->param('questionid', $question->id);
                    $url->param('action', 'deletequestion');
                    $buttons = html_writer::link($url, TRASH, $rowbuttonattributes) .' ';
                    $url->param('action', 'editquestion');
                    $buttons .= html_writer::link($url, COG, $rowbuttonattributes) . ' ';
                    $table->data[] = array($question->name . ' ' . $mandatory, $buttons);
                }
            }
            if (!empty($table->data)) {
                $output .= $OUTPUT->container(html_writer::table($table));
            }

            // Show link to add a new question.
            $url = clone($PAGE->url);
            $url->param('action', 'addquestion');
            $url->param('sectionid', $sectionid);
            $url->param('surveyid', $surveyid);
            $output .= $OUTPUT->container(html_writer::link($url, PLUS.'&nbsp;'.get_string('addquestion', 'block_voice'),
                $buttonattributes));
            $output .= html_writer::empty_tag('hr');
        }
    } else {
        $output .= $OUTPUT->container(html_writer::tag('p', get_string('nosections', 'block_voice')));
    }

    // Show link to add a new section.
    $url = clone($PAGE->url);
    $url->param('action', 'addsection');
    $url->param('surveyid', $surveyid);
    $output .= $OUTPUT->container(html_writer::link($url, PLUS.'&nbsp;'.get_string('addsection', 'block_voice'), $buttonattributes));
}

// Show the form to add a new survey.
if ($action == 'add') {
    $output .= $OUTPUT->heading(get_string('addsurvey', 'block_voice'), 2);
    $mform = new survey_form();
    $output .= $mform->render();
}

// Show the form to edit a survey.
if ($action == 'edit') {
    $surveyid = required_param('surveyid', PARAM_INT);
    $output .= $OUTPUT->heading(get_string('edit', 'block_voice'), 2);
    $survey = $DB->get_record_sql('SELECT * FROM {block_voice_survey} WHERE id = :surveyid',
        array ('surveyid' => $surveyid));
    if ($survey && $surveyid) {
        $mform = new survey_form(null, array('intro' => $survey->intro, 'surveyid' => $surveyid));
        $mform->set_data($survey);
        $output .= $mform->render();
    }
}

// Show the form to delete a survey.
if ($action == 'delete') {
    $surveyid = required_param('surveyid', PARAM_INT);
    $surveys = $DB->get_records_sql('SELECT * FROM {block_voice_survey} WHERE id = :surveyid ORDER BY seq ASC',
        array ('surveyid' => $surveyid));
    $data = $surveys[$surveyid];
    $data->timemodified = time();
    $undo = optional_param('undo', 0, PARAM_INT);

    // Reverse deletion.
    if ($undo) {
        $data->active = 1;
        $success = $DB->update_record('block_voice_survey', $data);
        foreach ($surveys as $id => $survey) {
            if ($survey->seq > $data->seq) {
                $survey->seq = $survey->seq + 1;
                $DB->update_record('block_voice_survey', $survey);
            }
        }
        if ($success) {
            utils::redirect_success($surveyconfigurl, get_string('surveyundeletesuccess', 'block_voice'));
        }
    } else {

        // Delete survey.
        $data->active = 0;
        $success = $DB->update_record('block_voice_survey', $data);
        foreach ($surveys as $id => $survey) {
            if ($survey->seq > $data->seq) {
                $survey->seq = $survey->seq - 1;
                $success = $DB->update_record('block_voice_survey', $survey);
            }
        }
        $url = clone($PAGE->url);
        $url->param('surveyid', $data->id);
        $url->param('action', 'delete');
        $url->param('undo', '1');
        $url->param('seq', $data->seq);
        if ($success) {
            $undolink = html_writer::link($url, get_string('undo', 'block_voice'));
            utils::redirect_success($surveyconfigurl, get_string('surveydeletesuccess', 'block_voice', $data->name) . ' ' . $undolink);
        }
    }
}

// Save a new/updated survey form.
if ($action == 'save') {
    // Get form data.
    $mform = new survey_form();
    if ($mform->is_cancelled()) {
        redirect($surveyconfigurl->out());
    }

    // Save data.
    $data = $mform->get_data();
    $data->timemodified = time();
    $data->active = 1;
    $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'context' => $context);
    $data = file_postupdate_standard_editor($data, 'intro', $editoroptions, $context);
    if ($data->surveyid != '') {
        $data->id = (int) $data->surveyid;
        $success = $DB->update_record('block_voice_survey', $data);
    } else {
        $surveys = $DB->get_records_sql('SELECT * FROM {block_voice_survey} WHERE active = 1 ORDER BY seq ASC');
        $data->seq = count($surveys) + 1;
        $success = $DB->insert_record('block_voice_survey', $data);
    }
    if ($success) {
        utils::redirect_success($surveyconfigurl, get_string('savesuccess', 'block_voice'));
    }
}

// Output the surveys.
if ($action == '' || $action == 'save' || $action == 'delete') {
    $output .= $OUTPUT->heading($title, 2);
    $surveys = $DB->get_records_sql('SELECT * FROM {block_voice_survey} WHERE active = 1 ORDER BY seq ASC');

    // Show list of surveys.
    if ($surveys) {
        $table = new html_table();
        $table->id = 'surveytable';
        $table->attributes = array('class' => 'table');
        foreach ($surveys as $id => $survey) {
            $format = $survey->format == SURVEY_FORMAT_LIKERT ? ELIPSIS : THUMB;
            $visibility = $survey->visible ? EYE_OPEN : EYE_CLOSED;
            $url = clone($PAGE->url);
            $url->param('surveyid', $survey->id);
            $url->param('action', 'questions');
            $buttons = html_writer::link($url, PLUS . '&nbsp;' . get_string('questions', 'block_voice'), $buttonattributes) .' ';
            $url->param('action', 'delete');
            $buttons .= html_writer::link($url, TRASH . '&nbsp;' . get_string('delete', 'block_voice'), $buttonattributes) .' ';
            $url->param('action', 'edit');
            $buttons .= html_writer::link($url, COG . '&nbsp;' . get_string('edit', 'block_voice'), $buttonattributes) . ' ';
            $table->data[] = array($survey->name . ' ' . $format . ' ' . $visibility, $buttons);
        }
        $output .= $OUTPUT->container(html_writer::table($table));
    } else {
        $output .= $OUTPUT->container(html_writer::tag('p', get_string('nosurveys', 'block_voice')));
    }

    // Show link to add a new survey.
    $url = clone($PAGE->url);
    $url->param('action', 'add');
    $output .= $OUTPUT->container(html_writer::link($url, PLUS.'&nbsp;'.get_string('addsurvey', 'block_voice'), $buttonattributes));
}

echo $OUTPUT->header();
echo $output;