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
 * Class containing renderers for the mod.
 *
 * @package   mod_dataview
 * @copyright 2020 David Herney @ BambuCo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_dataview\output;
defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;

/**
 * Class containing data for the module.
 *
 * @copyright 2020 David Herney @ BambuCo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class main implements renderable, templatable {

    /**
     * @var object Course module.
     */
    private $dataview = null;

    /**
     * Constructor.
     *
     * @param object $dataview Instance info
     */
    public function __construct($dataview) {
        global $CFG;

        $this->dataview = $dataview;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return array Context variables for the template
     */
    public function export_for_template(renderer_base $output) {
        global $CFG, $PAGE, $DB;

        $data = $DB->get_record('data', array('id' => $this->dataview->dataid), '*', MUST_EXIST);

        $fullfields = $DB->get_records('data_fields', array('dataid' => $data->id));

        $fields = explode("\n", $this->dataview->fields);

        $finalfields = [];
        $cm = get_coursemodule_from_instance('dataview', $this->dataview->id, 0, false, MUST_EXIST);
        foreach ($fields as $field) {
            $field = trim($field);
            foreach ($fullfields as $one) {
                if ($field == $one->name) {
                    $control = $this->get_searchcontrol($one, $data, $cm);

                    if ($control) {
                        if ($one->type == 'checkbox') {
                            $slides = explode('<br />', $control);

                            // Remove the "select all" option.
                            array_pop($slides);

                            $control = '<div class="option">' .
                                            implode('</div><div class="option">', $slides) .
                                        '</div>';
                        }

                        $finalfields[] = $one;
                        $one->control = $control;
                    }
                }
            }
        }

        $recordsbypage = ['', 1, 10, 20, 50, 100, 200];

        $PAGE->requires->string_for_js('goto', 'mod_dataview');

        $defaultvariables = [
            'baseurl' => $CFG->wwwroot,
            'fields' => $finalfields,
            'listtemplate' => $this->dataview->listtemplate,
            'singletemplate' => $this->dataview->singletemplate,
            'cansort' => count($finalfields) > 1,
            'recordsbypage' => $recordsbypage,
        ];

        return $defaultvariables;
    }

    private function get_searchcontrol($field, $data, $cm) {
        global $CFG;

        require_once($CFG->dirroot . '/mod/data/field/' . $field->type . '/field.class.php');
        $searchfield = 'data_field_' . $field->type;
        $searchfield = new $searchfield($field, $data, $cm);

        $control = $searchfield->display_search_field();

        return $control;
    }
}
