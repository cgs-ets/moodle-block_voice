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
 * Provides the {@link block_voice\controllers\editform} class.
 *
 * @package   block_voice
 * @copyright 2021 Michael Vangelovski
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_voice\controllers;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/voice/lib.php');

/**
 * privileges functions
 */
class editform {

    /**
     * Get's the questions (including sections) for a survey.
     *
     * @param  int  $surveyid
     * @return array
     */
    public static function get_survey_questions_html($instanceid, $surveyid) {
        global $DB, $OUTPUT;

        // Get selected questions for the block instance and mark the questions as checked.
        $data = $DB->get_record('block_instances', array('id' => $instanceid), '*', MUST_EXIST); 
        $block_instance = block_instance('voice', $data);
        $selected = explode(',', $block_instance->config->questionscsv);

        // Get sections.
        $sections = block_voice_get_sections($surveyid);

        // Load questions for sections.
        foreach ($sections as &$section) {
            $questions = block_voice_get_questions_by_section($section->id);
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


}
