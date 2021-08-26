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
 * Provides the block_voice/survey module
 *
 * @package   block_voice
 * @category  output
 * @copyright 2021 Michael Vangelovski
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module block_voice/survey
 */
 define(['jquery', 'core/log', 'core/ajax'], 
    function($, Log, Ajax) {    
    'use strict';

    /**
     * Initializes the survey component.
     */
    function init(instanceid) {
        Log.debug('block_voice/survey: initializing');

        var rootel = $('.survey');

        if (!rootel.length) {
            Log.error('block_voice/survey: rootel \'.survey\' not found!');
            return;
        }

        var survey = new Survey(instanceid, rootel);
        survey.main();
    }

    /**
     * The constructor
     *
     * @constructor
     * @param int rootel
     * @param {jQuery} rootel
     */
    function Survey(instanceid, rootel) {
        var self = this;
        self.instanceid = parseInt(instanceid);
        self.rootel = rootel;
    }

    /**
     * Run the survey js.
     *
     */
     Survey.prototype.main = function () {
        var self = this;

        // Select a question checkbox.
        self.rootel.on('change', '.answer', function () {
            var input = $(this);
            if (input.is(':checked')) {
                self.submitAnswer(input);
            }
        });

        self.rootel.find('.questions').removeClass('loading');

    };

    Survey.prototype.submitAnswer = function (input) {
        var self = this;
        var label = input.closest('label');
        var question = input.closest('.question');

        label.attr('data-status', 'saving');
        question.attr('data-status', 'saving');
        question.find('.answer').attr('disabled', true);

        // Save via ajax.
        var data = {
            'instanceid': self.instanceid,
            'questionid': question.data('id'),
            'responsevalue': input.val(),
        };
        Ajax.call([{
            methodname: 'block_voice_survey_api',
            args: { 
                action: 'submit_answer',
                data: JSON.stringify(data),
            },
            done: function(html) {
                // Clear existing selected.
                question.find('label').attr('data-status', '');
                // Set status and label.
                label.attr('data-status', 'selected');
                question.attr('data-status', 'saved');
                question.find('.answer').attr('disabled', false);
            },
            fail: function(reason) {
                label.attr('data-status', '');
                question.attr('data-status', 'unsaved');
                question.find('.answer').attr('disabled', false);
                Log.debug(reason);
            }
        }]);
    };

    return {
        init: init
    };
});