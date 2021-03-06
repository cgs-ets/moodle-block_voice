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
 * Student Voice page for student surveys
 *
 * @package    block_voice
 * @copyright  2021 Michael Vangelovski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Include required files.
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/blocks/voice/lib.php');

use \block_voice\controllers\survey;

$courseid = required_param('course', PARAM_INT);
$instanceid = required_param('id', PARAM_INT);

require_login();

// Setup page and context.
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($courseid);
$PAGE->set_context($context);

// Only staff.
require_capability('block/voice:addinstance', $context);

$studentcompletionsurl = new moodle_url('/blocks/voice/studentcompletions.php', array(
    'course' => $courseid,
    'id' => $instanceid,
));
$PAGE->set_url($studentcompletionsurl);

$surveyinstance = survey::get_survey_instance($instanceid);
$title = $surveyinstance->title;
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php', array('id' => $courseid)));
$PAGE->navbar->add($title, null);

// Add css.
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/blocks/voice/voice.css', array('nocache' => rand())));
// Add listjs
$PAGE->requires->js( new moodle_url($CFG->wwwroot . '/blocks/voice/js/listjs/1.5.0/list.min.js'), true );

$output = $OUTPUT->header();
$output .= survey::get_student_completions_html($courseid, $instanceid);

// Add scripts.
$PAGE->requires->js_call_amd('block_voice/studentcompletions', 'init', [
    'instanceid' => $instanceid,
]);

$output .= $OUTPUT->footer();
echo $output;

