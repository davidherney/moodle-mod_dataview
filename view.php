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
 * Data view module
 *
 * @package mod_dataview
 * @copyright 2020 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once('lib.php');
require_once('classes/output/renderer.php');
require_once('classes/output/main.php');

$id        = optional_param('id', 0, PARAM_INT);        // Course Module ID
$vid       = optional_param('v', 0, PARAM_INT);         // Dataview id

if ($id) {
    $cm = get_coursemodule_from_id('dataview', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $dataview = $DB->get_record('dataview', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $dataview = $DB->get_record('dataview', array('id' => $vid), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('dataview', $dataview->id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $id = $cm->id;
}

require_course_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/dataview:view', $context);

$PAGE->set_context($context);
$PAGE->set_url('/mod/dataview/view.php');
$PAGE->set_pagelayout('incourse');
$PAGE->set_heading($dataview->name);
$PAGE->set_title($dataview->name);

echo $OUTPUT->header();

$renderable = new \mod_dataview\output\main($dataview);
$renderer = $PAGE->get_renderer('mod_dataview');

$syscontext = context_system::instance();

$PAGE->requires->js_call_amd('mod_dataview/main', 'init', array($dataview->id));

echo $renderer->render($renderable);

echo $OUTPUT->footer();