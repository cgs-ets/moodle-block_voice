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
 * @copyright  2021 Michael de Raadt, Michael Vangelovski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once($CFG->libdir.'/formslib.php');

// Global defaults.
const SURVEY_FORMAT_LIKERT = 0;
const SURVEY_FORMAT_THUMBS = 1;

/**
 * Get all surveys.
 *
 * @param bool hidden controls whether only visible surveys are returned
 * @return array of surveys
 */
function block_voice_get_surveys($hidden = false) {
    global $DB;

    $where = '';
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
function block_voice_get_sections($surveyid) {
    global $DB;

    if (empty($surveyid)) {
        return [];
    }

    $sql = "SELECT *
              FROM {block_voice_section}
             WHERE surveyid = ?
          ORDER BY seq ASC";

    $sections = $DB->get_records_sql($sql, array($surveyid));

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
function block_voice_get_questions($surveyid) {
    global $DB;

    $sections = block_voice_get_sections($surveyid);
    if (empty($sections)) {
        return [];
    }

    list($insql, $inparams) = $DB->get_in_or_equal(array_column($sections, 'id'));
    $sql = "SELECT *
              FROM {block_voice_question}
             WHERE sectionid $insql
          ORDER BY seq ASC";
    $questions = $DB->get_records_sql($sql, $inparams);
    if (empty($questions)) {
        return [];
    }

    return $questions;
}