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
 * Provides the {@link block_voice\controllers\setup} class.
 *
 * @package   block_voice
 * @copyright 2021 Michael Vangelovski
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_voice\controllers;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/voice/lib.php');

/**
 * Setup controller.
 */
class setup {

    /**
     * Get's the questions (including sections) for a survey.
     *
     * @param  int  $instanceid
     * @return block_instance
     */
    public static function get_block_instance($instanceid) {
        global $DB;
        $data = $DB->get_record('block_instances', array('id' => $instanceid), '*', MUST_EXIST); 
        return block_instance('voice', $data);
    }

    /**
     * Get's the questions by block instance id.
     *
     * @param  int  $instanceid
     * @return array
     */
    public static function get_block_question_ids($instanceid) {
        $blockinstance = static::get_block_instance($instanceid);
        return explode(',', $blockinstance->config->questionscsv);
    }

    /**
     * Get's the survey config, including sections and questions.
     *
     * @param  int  $surveyid
     * @return array
     */
    public static function get_survey_config($instanceid) {
        global $DB, $OUTPUT;

        // Get selected questions for the block instance and mark the questions as checked.
        $blockinstance = static::get_block_instance($instanceid);
        $config = $blockinstance->config;
        $selected = explode(',', $config->questionscsv);

        // Get survey.
        $survey = static::get_survey($config->survey);

        // Get sections.
        $sections = static::get_sections($config->survey);

        // Load questions for sections.
        foreach ($sections as $i => &$section) {
            $questions = static::get_questions_by_section($section->id, $selected);
            if (empty($questions)) {
                unset($sections[$i]);
            } else {
                $section->questions = $questions;
            }
        }
        $survey->sections = $sections;
        $config->survey = $survey;
        return $config;
    }


    /**
     * Get's the survey config without sections.
     *
     * @param  int  $instanceid. Block instance id.
     * @return array
     */
    public static function get_survey_config_flat($instanceid) {
        global $DB, $OUTPUT;

        // Get selected questions for the block instance and mark the questions as checked.
        $blockinstance = static::get_block_instance($instanceid);
        $config = $blockinstance->config;
        $selected = explode(',', $config->questionscsv);

        // Get survey.
        $survey = static::get_survey($config->survey);

        // Get questions.
        $survey->questions = static::get_questions_by_survey($survey->id, $selected);
        $config->survey = $survey;
        return $config;
    }




    /**
     * Get's the edit_form questions html for a survey.
     *
     * @param  int  $surveyid
     * @return array
     */
    public static function get_survey_questions_html($instanceid, $surveyid) {
        global $DB, $OUTPUT;

        // Get selected questions for the block instance and mark the questions as checked.
        $data = $DB->get_record('block_instances', array('id' => $instanceid), '*', MUST_EXIST); 
        $blockinstance = block_instance('voice', $data);
        $selected = explode(',', $blockinstance->config->questionscsv);

        // Get sections.
        $sections = static::get_sections($surveyid);

        // Load questions for sections.
        foreach ($sections as &$section) {
            $questions = static::get_questions_by_section($section->id);
            if ($selected) {
                foreach ($questions as &$question) {
                    $question->checked = false;
                    if (in_array($question->id, $selected) || $question->mandatory) {
                        $question->checked = true;
                    }
                }
            }
            $section->questions = $questions;
        }

        // Render the sections/questions.
        return $OUTPUT->render_from_template('block_voice/editform_questions', ['sections' => $sections]);
    }

    /**
     * Get a survey by id.
     *
     * @param bool hidden controls whether only visible surveys are returned
     * @return array of surveys
     */
    public static function get_survey($id) {
        global $DB;

        $survey = $DB->get_record('block_voice_survey', array('id' => $id));

        return $survey;
    }

    /**
     * Get all surveys.
     *
     * @param bool hidden controls whether only visible surveys are returned
     * @return array of surveys
     */
    public static function get_surveys($hidden = false) {
        global $DB;

        $where = '';
        if (!$hidden) {
            $where = "WHERE visible = 1";
        }

        $surveys = array_values($DB->get_records_sql("SELECT * FROM {block_voice_survey} $where ORDER BY seq ASC"));

        return $surveys;
    }

    /**
     * Get sections.
     *
     * @param int surveyid ID for survey containing sections
     * @return array of sections
     */
    public static function get_sections($surveyid) {
        global $DB;

        if (empty($surveyid)) {
            return [];
        }

        $sql = "SELECT *
                FROM {block_voice_section}
                WHERE surveyid = ?
            ORDER BY seq ASC";

        $sections = array_values($DB->get_records_sql($sql, array($surveyid)));

        if (empty($sections)) {
            return [];
        }

        return $sections;
    }

    /**
     * Get survey questions.
     *
     * @param int surveyid ID for survey containing questions
     * @return array of questions
     */
    public static function get_questions_by_section($sectionid, $selected = false) {
        global $DB;

        $sql = "SELECT *
                FROM {block_voice_question}
                WHERE sectionid  = ?
            ORDER BY seq ASC";
        $params = array($sectionid);
        $questions = array_values($DB->get_records_sql($sql, $params));
        if (empty($questions)) {
            return [];
        }

        if ($selected) {
            foreach ($questions as $j => &$question) {
                if (in_array($question->id, $selected) || $question->mandatory) {
                    // Question needs to be included
                } else {
                    unset($questions[$j]);
                }
            }
        }

        return $questions;
    }

    /**
     * Get survey questions.
     *
     * @param int surveyid ID for survey containing questions
     * @return array of questions
     */
    public static function get_questions_by_survey($surveyid, $selected) {
        global $DB;

        $sections = static::get_sections($surveyid);
        if (empty($sections)) {
            return [];
        }

        list($insql, $inparams) = $DB->get_in_or_equal(array_column($sections, 'id'));
        $sql = "SELECT *
                  FROM {block_voice_question}
                 WHERE sectionid $insql
              ORDER BY seq ASC";
        $questions = array_values($DB->get_records_sql($sql, $inparams));
        if (empty($questions)) {
            return [];
        }

        if ($selected) {
            foreach ($questions as $j => &$question) {
                if (in_array($question->id, $selected) || $question->mandatory) {
                    // Question needs to be included
                } else {
                    unset($questions[$j]);
                }
            }
        }

        return $questions;
    }


}
