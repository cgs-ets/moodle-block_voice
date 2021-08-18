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
    public static function get_block_html_for_student($instanceid, $userid) {
        global $DB, $OUTPUT;

        // Check whether the survey is completed by this student.
        $completed = static::check_overall_completion($instanceid, $userid);
        $studentsurveyurl = new moodle_url('/blocks/voice/studentsurvey.php', array(
            'id' => $instanceid,
        ));

        // Render the block.
        return $OUTPUT->render_from_template('block_voice/student_view', array(
            'completed' => $completed,
            'studentsurveyurl' => $studentsurveyurl->out(),
        ));
    }

    /**
     * Render a block survey for a student.
     */
    public static function get_survey_html_for_student($instanceid, $userid) {
        global $DB, $OUTPUT;

        // Get block questions.
        $config = setup::get_config_with_sections($instanceid);
        echo "<pre>"; var_export($config); exit;

        // Merge user responses.
        static::load_user_survey_responses($config, $userid);

        // Render the survey.
        $OUTPUT->render_from_template('block_voice/survey', $config);


    }

    /**
     * Check if the survey is complete and set overall completion.
     *
     * @return array
     */
    public static function determine_completion($instanceid, $userid) {
        global $DB;

        // Check whether the survey is completed by this student.
        $completed = static::has_student_completed_by_questions($instanceid, $userid);

        if ($completed) {
            // Set overall completion time.
        }
    }

    /**
     * Check if the survey is completed by a user.
     *
     * @return int. false if not completed, time if the survey was completed.
     */
    public static function check_overall_completion($instanceid, $userid) {
        global $DB;

        // Check whether the survey is completed by this student.
        $data = $DB->get_record('block_voice_surveyresponse', array('blockinstanceid' => $instanceid));
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
        foreach ($questions as $question) {
            $response = $DB->get_record('block_voice_questionresponse', array('questionid' => $question->id, 'userid' => $userid));
            if (empty($response)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get survey questions with existing user responses.
     *
     * @return array
     */
    public static function load_user_survey_responses($config, $userid) {
        global $DB;

        // Look for questions that have not had a response.
        foreach ($config->sections as &$section){
            foreach ($section->questions as &$question) {
                $response = $DB->get_record('block_voice_questionresponse', array('questionid' => $question->id, 'userid' => $userid));
                if (!empty($response)) {
                    $question->responseid = $response->id;
                    $question->responsevalue = $response->responsevalue;
                }
            }
        }

        return $config;
    }
    
}
