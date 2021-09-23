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
 * Student Voice block settings
 *
 * @package   block_voice
 * @copyright 2021 Michael de Raadt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $context = CONTEXT::instance_by_id(CONTEXT_SYSTEM);
    if (has_capability('block/voice:administer', $context)) {
        $settings->add(new admin_setting_heading('block_voice/voicesurveyconfig', '',
            '<ul><li><a href="'.$CFG->wwwroot.'/blocks/voice/surveyconfig.php">'.
            get_string('voicesurveyconfig', 'block_voice').'</a></li></ul>'));
    }

    /*$settings->add(new admin_setting_configcolourpicker('block_voice/completedcolour',
        get_string('completedcolour_title', 'block_voice'),
        get_string('completedcolour_descr', 'block_voice'),
        get_string('completedcolour', 'block_voice'),
        null )
    );

    $settings->add(new admin_setting_configcolourpicker('block_voice/notcompletedcolour',
        get_string('notcompletedcolour_title', 'block_voice'),
        get_string('notcompletedcolour_descr', 'block_voice'),
        get_string('notcompletedcolour', 'block_voice'),
        null )
    );*/

    $roles = array();
    $rows = $DB->get_records_sql(
           "SELECT r.id, r.shortname
              FROM {role} r
        INNER JOIN {role_context_levels} rl
                ON rl.roleid = r.id
             WHERE contextlevel = 50"
    );
    foreach ($rows as $role) {
        $roles[$role->id] = $role->shortname;
    }
    $settings->add(new admin_setting_configmultiselect('block_voice/surveyroles',
        get_string('surveyroles', 'block_voice'), '',
        array(3), $roles)); // "3" is editing teacher, default.
}