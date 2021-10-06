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
     * Get's the survey config without sections.
     *
     * @param  int  $instanceid. Block instance id.
     * @return array
     */
    public static function get_survey_instance_expanded($instanceid) {
        global $DB, $OUTPUT;

        // Get selected questions for the block instance and mark the questions as checked.
        $surveyinstance = static::get_survey_instance($instanceid);
        $selected = explode(',', $surveyinstance->questionscsv);

        // Get survey.
        $survey = setup::get_survey($surveyinstance->surveyid);

        // Get questions.
        $survey->questions = setup::get_questions_by_survey($survey->id, $selected);
        $surveyinstance->survey = $survey;

        return $surveyinstance;
    }

    /**
     * Get a single response.
     *
     * @return array
     */
    public static function get_survey_instance($instanceid) {
        global $DB;

        $sql = "SELECT *
                  FROM {block_voice_teachersurvey}
                 WHERE blockinstanceid = ?";
        $teachersurvey = $DB->get_record_sql($sql, array($instanceid));

        $sql = "SELECT *
                  FROM {block_voice_surveyquestions}
                 WHERE teachersurveyid = ?";
        $teachersurveyquestions = $DB->get_record_sql($sql, array($teachersurvey->id));
        
        $teachersurvey->questionscsv = implode(',', $teachersurveyquestions);

        return $teachersurvey;
    }

    /**
     * Get's the questions by block instance id.
     *
     * @param  int  $instanceid
     * @return array
     */
    public static function get_survey_question_ids($instanceid) {
        $surveyinstance = static::get_survey_instance($instanceid);
        return explode(',', $surveyinstance->questionscsv);
    }

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
        $percentcompleted = 0;
        $percentinprogress = 0;
        $percentnotstarted = 0;
        if ($numstudents) {
            $percentcompleted = ($numcompleted / $numstudents) * 100;
            $percentinprogress = ($numinprogress / $numstudents) * 100;
            $percentnotstarted = ($numnotstarted / $numstudents) * 100;
        }

        // Render the block.
        return $OUTPUT->render_from_template('block_voice/teacher_view', array(
            'studentcompletionsurl' => $studentcompletionsurl->out(false),
            'percentcompleted' => $percentcompleted,
            'percentinprogress' => $percentinprogress,
            'percentnotstarted' => $percentnotstarted,
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
        $config = static::get_survey_instance_expanded($instanceid);
        
        // Export for template.
        $data = array(
            'instanceid' => $instanceid,
            'userid' => $userid,
            'courseid' => $courseid,
            'config' => $config,
        );
        $config = static::export_for_survey($data, true, true);

        // Render the survey.
        return $OUTPUT->render_from_template('block_voice/survey', $config);
    }

    /**
     * Render student completions.
     */
    public static function get_student_completions_html($courseid, $instanceid) {
        global $OUTPUT;

        $surveyinstance = survey::get_survey_instance($instanceid);
        $students = static::get_student_completion_data($courseid, $instanceid);

        // Export for template.
        $data = array(
            'config' => $surveyinstance,
            'students' => $students,
            'courseid' => $courseid,
        );

        $data = static::export_for_studentcompletions($data);
        //echo "<pre>"; var_export($data); exit;

        // Render the survey.
        return $OUTPUT->render_from_template('block_voice/student_completions', $data);
    }



    public static function get_survey_teacher($instanceid) {
        $config = static::get_survey_instance_expanded($instanceid);
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
        $questions = static::get_survey_question_ids($instanceid);

        // Look for questions that have not had a response.
        foreach ($questions as $questionid) {
            $response = static::get_response($instanceid, $questionid, $userid);
            if (empty($response)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get a single response.
     *
     * @return array
     */
    public static function get_response($instanceid, $questionid, $userid) {
        global $DB;

        $sql = "SELECT *
                  FROM {block_voice_questionresponse} q 
            INNER JOIN {block_voice_surveyresponse} s
                    ON q.surveyresponseid = s.id
                 WHERE s.blockinstanceid = ?
                   AND q.questionid = ?
                   AND q.userid = ?";
        return $DB->get_record_sql($sql, array($instanceid, $questionid, $userid));
    }

    /**
     * Stats for student view.
     *
     * @return array
     */
    public static function get_student_survey_stats($instanceid, $userid) {
        global $DB;

        // Get block questions.
        $questions = static::get_survey_question_ids($instanceid);

        // Get responses.
        $responses = array();
        foreach ($questions as $questionid) {
            $response = static::get_response($instanceid, $questionid, $userid);
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
        $config = static::get_survey_instance_expanded($instanceid);

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
            //$roles = get_user_roles($context, $user->id, false);
            //$roles = array_column($roles, 'roleid');
            //if (in_array(5, $roles) && !in_array(4, $roles)) { // 5 = student. 4 = teacher
            // Switch is based on cap, not role.
            if ( ! has_capability('block/voice:addinstance', $context, $user)) {
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
     * Student completions.
     *
     * @return array
     */
    public static function get_student_completion_data($courseid, $instanceid) {
        global $DB;

        $context = \context_course::instance($courseid);
        $config = static::get_survey_instance_expanded($instanceid);

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

        $students = array();
        foreach ($users as $user) {
            if ( ! has_capability('block/voice:addinstance', $context, $user)) {
                // Check whether completed, inprogress, or not started.
                list($questionsnum, $answerednum) = static::get_student_survey_stats($instanceid, $user->id);
                $user->completed = $answerednum == $questionsnum;
                $user->notstarted = $answerednum == 0;
                $user->inprogress = ($answerednum > 0 && !$user->completed);
                $students[] = $user;
            }
        }

        usort($students, function($a, $b) {return strcmp($a->lastname, $b->lastname);});


        return $students;
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
     * Sort questions by defined order.
     *
     * @return array
     */
    public static function sort_by_order(&$config, $questionorder) {
        global $DB;
        if (empty($questionorder)) {
            return;
        }

        if (!isset($config->survey->questions)) {
            return;
        }
        
        $questions = array();
        foreach ($config->survey->questions as $question) {
            $questions[$question->id] = $question;
        }

        $config->survey->questions = array_values(array_replace(array_flip(explode(',', $questionorder)), $questions));        
    }


    /**
     * Add some flesh to the config.
     *
     * @return array
     */
    public static function export_for_survey($data, $loadresponses = false, $randomise = false) {
        global $DB;
        
        $data['config']->survey->islikert = ($data['config']->survey->format == SURVEY_FORMAT_LIKERT);
        $data['config']->survey->isthumbs = ($data['config']->survey->format == SURVEY_FORMAT_THUMBS);
        $data['config']->survey->formatname = ($data['config']->survey->format == SURVEY_FORMAT_THUMBS) ? 'thumbs' : 'likert';
        $data['config']->courseurl = new moodle_url('/course/view.php', array('id' => $data['courseid']));

        $data['config']->teacher = \core_user::get_user($data['config']->teacher);
        block_voice_load_user_display_info($data['config']->teacher);

        // Export questions.
        if (isset($data['config']->survey->questions)) {
            foreach ($data['config']->survey->questions as &$question) {
                // Rewrite pluginfile urls.
                $context = \context_system::instance();
                $question->questiontext = file_rewrite_pluginfile_urls($question->questiontext, 'pluginfile.php', $context->id,
	        		'block_voice', 'questiontext', $question->id);

                // Add answers to questions.
                $question->answers = array();
                if ($data['config']->survey->islikert) {
                    $question->answers = LIKERT_ANSWERS;
                }
                if ($data['config']->survey->isthumbs) {
                    $question->answers = THUMBS_ANSWERS;
                }

                // Add responses to questions/answers.
                if ($loadresponses) {
                    $response = $DB->get_record('block_voice_questionresponse', array('questionid' => $question->id, 'userid' => $data['userid']));
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

        // Check if there is an overall survey response yet.
        $surveyresponse = $DB->get_record('block_voice_surveyresponse', array(
            'blockinstanceid' => $data['instanceid'],
            'userid' => $data['userid'],
        ));
        if (empty($surveyresponse)) {
            $timenow = time();
            $surveyresponse = new \stdClass();
            $surveyresponse->blockinstanceid = $data['instanceid'];
            $surveyresponse->userid = $data['userid'];
            $surveyresponse->timecreated = $timenow;
            $surveyresponse->timecompleted = 0;
            $surveyresponse->timemodified = $timenow;
            $surveyresponse->questionorder = '';
            if ($randomise) {
                static::randomise_questions($data['config']);
                $questionids = array_column($data['config']->survey->questions, 'id');
                $surveyresponse->questionorder = implode(',', $questionids);
            }
            $surveyresponse->id = $DB->insert_record('block_voice_surveyresponse', $surveyresponse);
        } else {
            if ($randomise && empty($surveyresponse->questionorder)) {
                static::randomise_questions($data['config']);
                $questionids = array_column($data['config']->survey->questions, 'id');
                $surveyresponse->questionorder = implode(',', $questionids);
                $DB->update_record('block_voice_surveyresponse', $surveyresponse);
            }
        }

        // Sort questions by initial question order.
        if ($randomise && $surveyresponse->questionorder) {
            static::sort_by_order($data['config'], $surveyresponse->questionorder);
        }
        
        return $data['config'];
    }

    public static function export_for_studentcompletions($data) {
        $data['config']->teacher = \core_user::get_user($data['config']->teacher);
        block_voice_load_user_display_info($data['config']->teacher);
        foreach($data['students'] as &$student) {
            block_voice_load_user_display_info($student);
        }
        $data['courseurl'] = new moodle_url('/course/view.php', array('id' => $data['courseid']));
        return $data;
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
