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
 * Add dataview form
 *
 * @package mod_dataview
 * @copyright 2020 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_dataview_mod_form extends moodleform_mod {

    function definition() {
        global $PAGE, $DB;

        $PAGE->force_settings_menu();

        $course = null;
        if ($this->current && $this->current->course) {
            $course = $DB->get_record('course', array('id' => $this->current->course), '*', MUST_EXIST);
        }

        $mform = $this->_form;

        $mform->addElement('header', 'generalhdr', get_string('general'));
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'48'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $this->standard_intro_elements();

        $mform->addElement('checkbox', 'showdescription', get_string('display_description', 'dataview'));
        $mform->setType('showdescription', PARAM_INT);

        $mform->addElement('textarea', 'fields', get_string('fields', 'mod_dataview'), array('cols' => 60, 'rows' => 5));

        $mform->addElement('textarea', 'listtemplate', get_string('listtemplate', 'mod_dataview'),
                            array('cols' => 60, 'rows' => 5));
        $mform->addHelpButton('listtemplate', 'listtemplate', 'mod_dataview');

        $mform->addElement('textarea', 'singletemplate', get_string('singletemplate', 'mod_dataview'),
                            array('cols' => 60, 'rows' => 5));
        $mform->addHelpButton('singletemplate', 'singletemplate', 'mod_dataview');

        $mform->addElement('textarea', 'customfilters', get_string('customfilters', 'mod_dataview'),
                            array('cols' => 60, 'rows' => 5));
        $mform->addHelpButton('customfilters', 'customfilters', 'mod_dataview');

        if ($course) {
            $list = array();
            $list = $DB->get_records_menu('data', array('course' => $course->id), 'name', 'id, name');

            $mform->addElement('select', 'dataid', get_string('dataid', 'mod_dataview'), $list);
        }

        $this->standard_coursemodule_elements();

//-------------------------------------------------------------------------------
// General buttons.
        $this->add_action_buttons();

    }

}
