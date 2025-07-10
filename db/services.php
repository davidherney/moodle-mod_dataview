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
 * Data view external functions and service definitions.
 *
 * @package    mod_dataview
 * @category   external
 * @copyright  2020 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$functions = array(

    'mod_dataview_get_dataviewlist_by_courses' => array(
        'classname'     => 'mod_dataview_external',
        'methodname'    => 'get_dataviewlist_by_courses',
        'description'   => 'Returns a list of dataviewlist in a provided list of courses, if no list is provided all dataviewlist that the user
                            can view will be returned.',
        'type'          => 'read',
        'capabilities'  => 'mod/dataview:view',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),

    'mod_dataview_query' => array(
        'classname' => 'mod_dataview_external',
        'methodname' => 'query',
        'description' => 'Get a records list',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => false,
        'capabilities' => 'mod/dataview:view'
    ),
);

$services = [];
