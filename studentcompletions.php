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
 * Student Voice page for student survey completions
 *
 * @package    block_voice
 * @copyright  2021 Michael de Raadt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Include required files.
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/blocks/voice/lib.php');

// Check user is logged in and capable of accessing the survey.
require_login();

/*
// Determine course and context.
$courseid = 1;
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_system::instance();
$action = optional_param('action', '', PARAM_ALPHANUMEXT); // Which page to show.
$buttonattributes = array('class' => 'btn btn-primary', 'style' => 'margin: 0 0 10px 5px;');

// Set up page parameters.
$PAGE->set_course($course);
$PAGE->requires->css('/blocks/voice/styles.css');
$PAGE->set_url('/blocks/voice/surveyconfig.php');
$PAGE->set_context($context);
$title = get_string('voicesurveyconfig', 'block_voice');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('report');

// Start page output.
echo $OUTPUT->header();
echo $OUTPUT->container_start('survey_config');
*/
// TODO: Add table showing students who have completed the survey.

