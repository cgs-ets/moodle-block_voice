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
 * Provides the block_voice/editform module
 *
 * @package   block_voice
 * @category  output
 * @copyright 2021 Michael Vangelovski
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module block_voice/editform
 */
 define(['jquery', 'core/log', 'core/ajax'], 
    function($, Log, Ajax) {    
    'use strict';

    /**
     * Initializes the editform component.
     */
    function init(instanceid) {
        Log.debug('block_voice/editform: initializing');

        var rootel = $('.mform');

        if (!rootel.length) {
            Log.error('block_voice/editform: rootel \'.mform\' not found!');
            return;
        }

        var editform = new EditForm(instanceid, rootel);
        editform.main();
    }

    /**
     * The constructor
     *
     * @constructor
     * @param int rootel
     * @param {jQuery} rootel
     */
    function EditForm(instanceid, rootel) {
        var self = this;
        self.instanceid = parseInt(instanceid);
        self.rootel = rootel;
    }

    /**
     * Run the editform js.
     *
     */
     EditForm.prototype.main = function () {
        var self = this;

        // Load questions on survey selection
        self.rootel.on('change', 'select[name="config_survey"]', function() {
            var select = $(this);
            self.loadQuestions(select.val());
        });

        // Select a question checkbox.
        self.rootel.on('change', 'input.question', function () {
            self.generateQuestionCSV();
        });

        //Initial load.
        self.rootel.find('select[name="config_survey"]').change();

    };

    EditForm.prototype.loadQuestions = function (surveyid) {
        var self = this;

        if (surveyid) {
            var data = {
                'instanceid': self.instanceid,
                'surveyid': surveyid,
            };
            Ajax.call([{
                methodname: 'block_voice_editform_api',
                args: { 
                    action: 'get_questions',
                    data: JSON.stringify(data),
                },
                done: function(html) {
                    self.rootel.find('#questions').html(html);
                },
                fail: function(reason) {
                    Log.debug(reason);
                }
            }]);
        }
    };

    EditForm.prototype.generateQuestionCSV = function () {
        var self = this;

        var input = self.rootel.find('input[name="config_questionscsv"]');

        // Convert csv into array.
        var questions = new Array();
        self.rootel.find('input.question:checked').each(function(){
            questions.push($(this).val());
        });
            

        // Update hidden input.
        input.val(questions.join());
    };

    return {
        init: init
    };
});