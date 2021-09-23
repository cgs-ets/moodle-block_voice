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
 * @package   block_voice
 * @copyright 2021 Michael Vangelovski
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_voice;

defined('MOODLE_INTERNAL') || die();

/**
 * Provides utility functions for this plugin.
 *
 * @package   block_voice
 * @copyright 2021 Michael Vangelovski
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class utils {

    public static function redirect_success($url, $notice) {
        redirect(
            $url->out(),
            $notice,
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
        exit;
    }

    /**
     * Helper function to get enrolled users.
     *
     * @param int $courseid
     * @param string $shortname
     * @return int[]
     */
    public static function get_users_by_role_shortname($courseid, $shortname) {
        global $DB;
        $context = \context_course::instance($courseid);
        $roleid = $DB->get_field('role', 'id', array('shortname'=> $shortname));
        $users = get_role_users($roleid, $context, false, 'u.id, u.firstname', 'u.firstname'); //last param is sort by. 
        return array_map('intval', array_column($users, 'id'));
    }

    /**
     * Helper function to get enrolled users.
     *
     * @param int $courseid
     * @param array $roleids
     * @return int[]
     */
    public static function get_users_by_role_ids($courseid, $roleids) {
        global $DB;
        $context = \context_course::instance($courseid);
        $users = array();
        foreach ($roleids as $roleid) {
            $roleusers = get_role_users($roleid, $context, false, 'u.id, u.firstname', 'u.firstname'); //last param is sort by. 
            foreach ($roleusers as $roleuser) {
                $id = intval($roleuser->id);
                $users[$id] = \core_user::get_user($id);
            }
        }
        return $users;
    }

   

}