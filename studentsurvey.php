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

use \block_voice\controllers\setup;
use \block_voice\controllers\survey;

$instanceid = optional_param('id', 0, PARAM_INT);

require_login();

// Page setup.
$context = context_system::instance();
$PAGE->set_context($context);
$studentsurveyurl = new moodle_url('/blocks/voice/studentsurvey.php', array(
    'id' => $instanceid,
));
$PAGE->set_url($studentsurveyurl);
$blockinstance = setup::get_block_instance($instanceid);
$title = $blockinstance->config->title;
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Add css.
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/blocks/voice/voice.css', array('nocache' => rand())));

$output = $OUTPUT->header();
$output .= survey::get_survey_html_for_student($instanceid, $USER->id);

// Add scripts.
$PAGE->requires->js_call_amd('block_voice/survey', 'init');

$output .= $OUTPUT->footer();
echo $output;

