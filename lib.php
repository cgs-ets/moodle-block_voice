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

const LIKERT_ANSWERS = array(
    '1' => array(
        'value' => '1',
        'name' => 'Strongly disagree',
    ),
    '2' => array(
        'value' => '2',
        'name' => 'Disagree',
    ),
    '3' => array(
        'value' => '3',
        'name' => 'Neutral',
    ),
    '4' => array(
        'value' => '4',
        'name' => 'Agree',
    ),
    '5' => array(
        'value' => '5',
        'name' => 'Strongly agree',
    ),
);

const THUMBS_ANSWERS = array(
    '1' => array(
        'value' => '1',
        'name' => '<i class="fa fa-thumbs-down" aria-hidden="true"></i>',
    ),
    '2' => array(
        'value' => '2',
        'name' => '<i class="fa fa-thumbs-up" aria-hidden="true"></i>',
    ),
);

/**
 * Helper function to add extra display info for user.
 *
 * @param stdClass $user
 * @return stdClass $user
 */
function block_voice_load_user_display_info(&$user) {
    global $PAGE;

    // Fullname.
    $user->fullname = fullname($user);
    $user->lastnamefirst = $user->lastname . ", " . $user->firstname;

    // Profile photo.
    $userphoto = new \user_picture($user);
    $userphoto->size = 2; // Size f2.
    $user->profilephoto = $userphoto->get_url($PAGE)->out(false);

    // Profile url.
    $user->profileurl = new \moodle_url('/user/profile.php', array('id' => 2));
    $user->profileurl = $user->profileurl->out(false);
}