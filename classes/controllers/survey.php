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
 * Provides the {@link block_voice\controllers\survey} class.
 *
 * @package   block_voice
 * @copyright 2021 Michael Vangelovski
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_voice\controllers;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/voice/lib.php');

use \block_voice\controllers\setup;
use \moodle_url;

/**
 * Survey controller.
 */
class survey {

    /**
     * Render the block for a student user.
     *
     * @return string
     */
    public static function get_block_html_for_student($courseid, $instanceid, $userid) {
        global $DB, $OUTPUT;

        $studentsurveyurl = new moodle_url('/blocks/voice/studentsurvey.php', array(
            'course' => $courseid,
            'id' => $instanceid,
        ));

        // Check whether the survey is completed by this student.
        list($questionsnum, $answerednum) = static::get_student_survey_stats($instanceid, $userid);
        $completed = $answerednum == $questionsnum;
        $notstarted = $answerednum == 0;
        $inprogress = ($answerednum > 0 && !$completed);

        // Render the block.
        return $OUTPUT->render_from_template('block_voice/student_view', array(
            'teacher' => static::get_survey_teacher($instanceid),
            'studentsurveyurl' => $studentsurveyurl->out(false),
            'answerednum' => $answerednum,
            'questionsnum' => $questionsnum,
            'completed' => $completed,
            'notstarted' => $notstarted,
            'inprogress' => $inprogress,
        ));
    }

    
    /**
     * Render the block for a teacher user.
     *
     * @return string
     */
    public static function get_block_html_for_teacher($courseid, $instanceid, $userid) {
        global $DB, $OUTPUT;

        $studentcompletionsurl = new moodle_url('/blocks/voice/studentcompletions.php', array(
            'course' => $courseid,
            'id' => $instanceid,
        ));

        list($numstudents, $numcompleted, $numinprogress, $numnotstarted) = static::get_teacher_survey_stats($courseid, $instanceid);
        $percentcompleted = ($numcompleted / $numstudents) * 100;
        $percentinprogress = ($numinprogress / $numstudents) * 100;

        // Render the block.
        return $OUTPUT->render_from_template('block_voice/teacher_view', array(
            'studentcompletionsurl' => $studentcompletionsurl->out(false),
            'percentcompleted' => $percentcompleted,
            'percentinprogress' => $percentinprogress,
            'numstudents' => $numstudents,
            'numcompleted' => $numcompleted,
            'numinprogress' => $numinprogress,
            'numnotstarted' => $numnotstarted,
        ));
    }

    /**
     * Render a block survey for a student.
     */
    public static function get_survey_html_for_student($courseid, $instanceid, $userid) {
        global $OUTPUT;

        // Get block questions.
        $config = setup::get_survey_config_flat($instanceid);
        
        // Export for template.
        $related = array(
            'userid' => $userid,
            'courseid' => $courseid,
        );
        $config = static::export($config, $related, true, true);

        // Render the survey.
        return $OUTPUT->render_from_template('block_voice/survey', $config);
    }

    public static function get_survey_teacher($instanceid) {
        $config = setup::get_survey_config_flat($instanceid);
        $teacher = \core_user::get_user($config->teacher);
        block_voice_load_user_display_info($teacher);
        return $teacher;
    }

    /**
     * Check if the survey is completed by a user.
     *
     * @return int. false if not completed, time if the survey was completed.
     */
    public static function check_overall_completion($instanceid, $userid) {
        global $DB;

        // Check whether the survey is completed by this student.
        $data = $DB->get_record('block_voice_surveyresponse', array('blockinstanceid' => $instanceid, 'userid' => $userid));
        if ($data && $data->timecompleted) {
            return $data->timecompleted;
        }

        return false;
    }

    /**
     * Check if the survey is completed by a user.
     *
     * @return array
     */
    public static function check_question_completion($instanceid, $userid) {
        global $DB;

        // Get block questions.
        $questions = setup::get_block_question_ids($instanceid);

        // Look for questions that have not had a response.
        foreach ($questions as $questionid) {
            $response = $DB->get_record('block_voice_questionresponse', array('questionid' => $questionid, 'userid' => $userid));
            if (empty($response)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Stats for student view.
     *
     * @return array
     */
    public static function get_student_survey_stats($instanceid, $userid) {
        global $DB;

        // Get block questions.
        $questions = setup::get_block_question_ids($instanceid);

        // Get responses.
        $responses = array();
        foreach ($questions as $questionid) {
            $response = $DB->get_record('block_voice_questionresponse', array('questionid' => $questionid, 'userid' => $userid));
            if($response) {
                $responses[] = $response;
            }
        }

        return array(count($questions), count($responses));
    }

    /**
     * Stats for staff view.
     *
     * @return array
     */
    public static function get_teacher_survey_stats($courseid, $instanceid) {
        global $DB;

        $context = \context_course::instance($courseid);
        $config = setup::get_survey_config_flat($instanceid);

        // Get survey students.
        $users = array();
        if ($config->group == 'all') {
            $users = get_enrolled_users($context);
        } else if (substr($config->group, 0, 9) == 'grouping-') {
            $grouping = (int) substr($config->group, 9);
            $users = groups_get_grouping_members($grouping);
        } else if (substr($config->group, 0, 6) == 'group-') {
            $group = (int) substr($config->group, 6);
            $users = groups_get_members($group);
        }

        $numstudents = 0;
        $numcompleted = 0;
        $numinprogress = 0;
        $numnotstarted = 0;
        foreach ($users as $user) {
            $roles = get_user_roles($context, $user->id, false);
            $roles = array_column($roles, 'roleid');
            if (in_array(5, $roles)) { // 5 = student.
                $numstudents++;
                // Check whether completed, inprogress, or not started.
                list($questionsnum, $answerednum) = static::get_student_survey_stats($instanceid, $user->id);
                $completed = $answerednum == $questionsnum;
                $notstarted = $answerednum == 0;
                $inprogress = ($answerednum > 0 && !$completed);
                if ($completed) {
                    $numcompleted++;
                }
                if ($notstarted) {
                    $numnotstarted++;
                }
                if ($inprogress) {
                    $numinprogress++;
                }
            }
        }

        return array($numstudents, $numcompleted, $numinprogress, $numnotstarted);
    }

    /**
     * Check if the survey has any responses.
     *
     * @return bool.
     */
    public static function has_responses($instanceid) {
        global $DB;

        // Check whether the survey has been started by anyone.
        $data = $DB->get_records('block_voice_surveyresponse', array('blockinstanceid' => $instanceid));
        if ($data) {
            return true;
        }

        return false;
    }

    /**
     * Randomise question order in config.
     *
     * @return array
     */
    public static function randomise_questions(&$config) {
        global $DB;

        if (isset($config->survey->questions)) {
            shuffle($config->survey->questions);
        }
    }

    /**
     * Add some flesh to the config.
     *
     * @return array
     */
    public static function export($config, $related, $loadresponses = false, $randomise = false) {
        global $DB;
        
        $config->survey->islikert = ($config->survey->format == SURVEY_FORMAT_LIKERT);
        $config->survey->isthumbs = ($config->survey->format == SURVEY_FORMAT_THUMBS);
        $config->survey->formatname = ($config->survey->format == SURVEY_FORMAT_THUMBS) ? 'thumbs' : 'likert';
        $config->courseurl = new moodle_url('/course/view.php', array('id' => $related['courseid']));

        $config->teacher = \core_user::get_user($config->teacher);
        block_voice_load_user_display_info($config->teacher);

        // Export questions.
        if (isset($config->survey->questions)) {
            foreach ($config->survey->questions as &$question) {
                // Add answers to questions.
                $question->answers = array();
                if ($config->survey->islikert) {
                    $question->answers = LIKERT_ANSWERS;
                }
                if ($config->survey->isthumbs) {
                    $question->answers = THUMBS_ANSWERS;
                }

                // Add responses to questions/answers.
                if ($loadresponses) {
                    $response = $DB->get_record('block_voice_questionresponse', array('questionid' => $question->id, 'userid' => $related['userid']));
                    if (!empty($response)) {
                        $question->responseid = $response->id;
                        $question->responsevalue = $response->responsevalue;
                        if (isset($question->answers[$question->responsevalue])) {
                            $question->hasreponse = true;
                            $question->answers[$question->responsevalue]['selected'] = true;
                        }
                    }
                }

                $question->answers = array_values($question->answers); // Drop keys for template.
            }
        }

        // Randomise question order.
        if ($randomise) {
            static::randomise_questions($config);
        }

        //echo "<pre>"; var_export($config); exit;
        return $config;

    }
    
    /**
     * Save an answer response.
     *
     * @return array
     */
    public static function submit_answer($instanceid, $questionid, $responsevalue) {
        global $DB, $USER;

        $timenow = time();

        // Check if there is an overall survey response yet.
        $surveyresponse = $DB->get_record('block_voice_surveyresponse', array(
            'blockinstanceid' => $instanceid,
            'userid' => $USER->id,
        ));

        if (empty($surveyresponse)) {
            $surveyresponse = new \stdClass();
            $surveyresponse->blockinstanceid = $instanceid;
            $surveyresponse->userid = $USER->id;
            $surveyresponse->timecreated = $timenow;
            $surveyresponse->timecompleted = 0;
            $surveyresponse->timemodified = $timenow;
            $surveyresponse->id = $DB->insert_record('block_voice_surveyresponse', $surveyresponse);
        } else {
            $surveyresponse->timemodified = $timenow;
            $DB->update_record('block_voice_surveyresponse', $surveyresponse);
        }

        // Check for answer.
        $questionresponse = $DB->get_record('block_voice_questionresponse', array(
            'questionid' => $questionid,
            'userid' => $USER->id,
        ));

        if (empty($questionresponse)) {
            $questionresponse = new \stdClass();
            $questionresponse->surveyresponseid = $surveyresponse->id;
            $questionresponse->questionid = $questionid;
            $questionresponse->userid = $USER->id;
            $questionresponse->responsevalue = $responsevalue;
            $questionresponse->timecreated = $timenow;
            $questionresponse->id = $DB->insert_record('block_voice_questionresponse', $questionresponse);
        } else {
            $questionresponse->responsevalue = $responsevalue;
            $questionresponse->timecreated = $timenow;
            $DB->update_record('block_voice_questionresponse', $questionresponse);
        }

        // Check for completion
        $completed = static::check_question_completion($instanceid, $userid);
        if ($completed) {
            $surveyresponse->timecompleted = $timenow;
            $DB->update_record('block_voice_surveyresponse', $surveyresponse);
        }

        return true;

    }
}
