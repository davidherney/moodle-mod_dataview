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
 * Library of functions and constants for module dataview
 *
 * @package mod_dataview
 * @copyright 2020 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @global object
 * @param object $dataview
 * @return bool|int
 */
function dataview_add_instance($dataview) {
    global $DB;

    $dataview->timemodified = time();

    $id = $DB->insert_record("dataview", $dataview);

    $completiontimeexpected = !empty($dataview->completionexpected) ? $dataview->completionexpected : null;
    \core_completion\api::update_completion_date_event($dataview->coursemodule, 'dataview', $id, $completiontimeexpected);

    return $id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @global object
 * @param object $dataview
 * @return bool
 */
function dataview_update_instance($dataview) {
    global $DB;

    $dataview->timemodified = time();
    $dataview->id = $dataview->instance;

    $completiontimeexpected = !empty($dataview->completionexpected) ? $dataview->completionexpected : null;
    \core_completion\api::update_completion_date_event($dataview->coursemodule, 'dataview', $dataview->id, $completiontimeexpected);

    return $DB->update_record("dataview", $dataview);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @global object
 * @param int $id
 * @return bool
 */
function dataview_delete_instance($id) {
    global $DB;

    if (! $dataview = $DB->get_record("dataview", array("id"=>$id))) {
        return false;
    }

    $result = true;

    $cm = get_coursemodule_from_instance('dataview', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'dataview', $dataview->id, null);

    if (! $DB->delete_records("dataview", array("id"=>$dataview->id))) {
        $result = false;
    }

    return $result;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 * See get_array_of_activities() in course/lib.php
 *
 * @global object
 * @param object $coursemodule
 * @return cached_cm_info|null
 */
function dataview_get_coursemodule_info($coursemodule) {
    global $DB;

    $dbparams = ['id' => $coursemodule->instance];
    $fields = 'id, name, intro, introformat';
    if (!$dataview = $DB->get_record('dataview', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $dataview->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $result->content = format_module_intro('dataview', $dataview, $coursemodule->id, false);
    }

    return $result;
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 *
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function dataview_reset_userdata($data) {

    // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
    // See MDL-9367.

    return array();
}

/**
 * @uses FEATURE_IDNUMBER
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return bool|null True if module supports feature, false if not, null if doesn't know
 */
function dataview_supports($feature) {
    switch($feature) {
        case FEATURE_IDNUMBER:                return true;
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return false;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_BACKUP_MOODLE2:          return true;

        default: return null;
    }
}

/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module data
 * @param  int $from the time to check updates from
 * @param  array $filter  if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.2
 */
function dataview_check_updates_since(cm_info $cm, $from, $filter = array()) {
    $updates = course_check_module_updates_since($cm, $from, array(), $filter);
    return $updates;
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @param int $userid User id to use for all capability checks, etc. Set to 0 for current user (default).
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_dataview_core_calendar_provide_event_action(calendar_event $event,
                                                      \core_calendar\action_factory $factory,
                                                      int $userid = 0) {
    return null;
}
