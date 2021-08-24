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
 * Provides {@link block_voice\external\api} class.
 *
 * @package   block_voice
 * @category  external
 * @copyright 2021 Michael Vangelovski
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

namespace block_voice\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use \block_voice\controllers\survey;


/**
 * Provides an external API of the plugin.
 *
 * @copyright 2021 Michael Vangelovski
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class survey_api extends external_api {
/**
     * Describes the structure of parameters for the function.
     *
     * @return external_function_parameters
     */
    public static function survey_api_parameters() {
        return new external_function_parameters([
            'action' =>  new external_value(PARAM_RAW, 'Action'),
            'data' => new external_value(PARAM_RAW, 'Data to process'),
        ]);
    }

    /**
     * API Controller
     *
     * @param int $query The search query
     */
    public static function survey_api($action, $data) {
        global $USER, $OUTPUT, $PAGE;

        // Setup context.
        $context = \context_user::instance($USER->id);
        self::validate_context($context);

        // Validate params.
        self::validate_parameters(self::survey_api_parameters(), compact('action', 'data'));

        if ($action == 'submit_answer') {
            $data = json_decode($data);
            return survey::submit_answer($data->instanceid, $data->questionid, $data->responsevalue);
        }

        return 0;
    }

    /**
     * Describes the structure of the function return value.
     *
     * @return external_single_structure
     */
    public static function survey_api_returns() {
         return new external_value(PARAM_RAW, 'Result');
    }
}