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
 * Provides the block_voice/studentcompletions module
 *
 * @package   block_voice
 * @category  output
 * @copyright 2021 Michael Vangelovski
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module block_voice/studentcompletions
 */
 define(['jquery', 'core/log'], 
 function($, Log) {    
    'use strict';

    /**
     * Initializes the studentcompletions component.
     */
    function init() {
        Log.debug('block_voice/studentcompletions: initializing');

        var rootel = $('#page-blocks-voice-studentcompletions');

        if (!rootel.length) {
            Log.error('block_voice/studentcompletions: #page-blocks-voice-studentcompletions not found!');
            return;
        }

        var studentcompletions = new StudentCompletions(rootel);
        studentcompletions.main();
    }

    /**
     * The constructor
     *
     * @constructor
     * @param {jQuery} rootel
     */
    function StudentCompletions(rootel) {
        var self = this;
        self.rootel = rootel;
    }

    /**
     * Run the js.
     *
     */
    StudentCompletions.prototype.main = function () {
        var self = this;

        var list = new List(
            'student-list', 
            { valueNames: [ 'col-studentname', 'col-status' ] }
        );

    };

    return {
        init: init
    };
});