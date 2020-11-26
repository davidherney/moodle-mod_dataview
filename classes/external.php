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
 * Data view external API
 *
 * @package    mod_dataview
 * @category   external
 * @copyright  2020 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.7
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");

/**
 * Data view external functions
 *
 * @package    mod_dataview
 * @category   external
 * @copyright  2020 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.7
 */
class mod_dataview_external extends external_api {

    /**
     * Describes the parameters for get_dataviewlist_by_courses.
     *
     * @return external_function_parameters
     */
    public static function get_dataviewlist_by_courses_parameters() {
        return new external_function_parameters (
            array(
                'courseids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'Course id'), 'Array of course ids', VALUE_DEFAULT, array()
                ),
            )
        );
    }

    /**
     * Returns a list of dataviewlist in a provided list of courses.
     * If no list is provided all dataviewlist that the user can view will be returned.
     *
     * @param array $courseids course ids
     * @return array of warnings and dataviewlist
     */
    public static function get_dataviewlist_by_courses($courseids = array()) {

        $warnings = array();
        $returneddataviewlist = array();

        $params = array(
            'courseids' => $courseids,
        );
        $params = self::validate_parameters(self::get_dataviewlist_by_courses_parameters(), $params);

        $mycourses = array();
        if (empty($params['courseids'])) {
            $mycourses = enrol_get_my_courses();
            $params['courseids'] = array_keys($mycourses);
        }

        // Ensure there are courseids to loop through.
        if (!empty($params['courseids'])) {

            list($courses, $warnings) = external_util::validate_courses($params['courseids'], $mycourses);

            // Get the dataviewlist in this course, this function checks users visibility permissions.
            // We can avoid then additional validate_context calls.
            $dataviewlist = get_all_instances_in_courses("dataview", $courses);
            foreach ($dataviewlist as $dataview) {
                $context = context_module::instance($dataview->coursemodule);
                // Entry to return.
                $dataview->name = external_format_string($dataview->name, $context->id);
                $options = array('noclean' => true);
                list($dataview->intro, $dataview->introformat) =
                    external_format_text($dataview->intro, $dataview->introformat, $context->id, 'mod_dataview', 'intro', null, $options);
                $dataview->introfiles = external_util::get_area_files($context->id, 'mod_dataview', 'intro', false, false);

                $returneddataviewlist[] = $dataview;
            }
        }

        $result = array(
            'dataviewlist' => $returneddataviewlist,
            'warnings' => $warnings
        );
        return $result;
    }

    /**
     * Describes the get_dataviewlist_by_courses return value.
     *
     * @return external_single_structure
     */
    public static function get_dataviewlist_by_courses_returns() {
        return new external_single_structure(
            array(
                'dataviewlist' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'Module id'),
                            'coursemodule' => new external_value(PARAM_INT, 'Course module id'),
                            'course' => new external_value(PARAM_INT, 'Course id'),
                            'name' => new external_value(PARAM_RAW, 'Data view name'),
                            'intro' => new external_value(PARAM_RAW, 'Data view contents'),
                            'introformat' => new external_format_value('intro', 'Content format'),
                            'introfiles' => new external_files('Files in the introduction text'),
                            'timemodified' => new external_value(PARAM_INT, 'Last time the dataview was modified'),
                            'section' => new external_value(PARAM_INT, 'Course section id'),
                            'visible' => new external_value(PARAM_INT, 'Module visibility'),
                            'groupmode' => new external_value(PARAM_INT, 'Group mode'),
                            'groupingid' => new external_value(PARAM_INT, 'Grouping id'),
                        )
                    )
                ),
                'warnings' => new external_warnings(),
            )
        );
    }





    /**
     * To validade input parameters
     * @return external_function_parameters
     */
    public static function query_parameters() {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'Dataview id'),
                'q' => new external_value(PARAM_TEXT, 'Query', VALUE_DEFAULT, '*'),
                'filters' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'key'   => new external_value(PARAM_TEXT, 'Filter key'),
                            'value' => new external_value(PARAM_TEXT, 'Course id number')),
                        'A single filter key/value pair'),
                    'Filters query list', VALUE_DEFAULT, array()
                ),
                'start' => new external_value(PARAM_INT, 'Records start', VALUE_DEFAULT, 0),
                'limit' => new external_value(PARAM_INT, 'Records limit', VALUE_DEFAULT, 20)
            )
        );
    }

    public static function query($id, $q, $filters, $start, $limit) {
        global $DB, $USER, $CFG;

        $dataview = $DB->get_record('dataview', array('id' => $id), '*', MUST_EXIST);

        $dataid = $dataview->dataid;

        $query = '';
        $querylist = array();
        $params = array();
        $i = 0;

        $q = trim($q);
        if (!empty($q)) {

            $pattern = '/"(.*?)"/i';
            if (preg_match_all($pattern, $q, $result, PREG_PATTERN_ORDER) && count($result) > 1) {
                // The 0 position is the result with quotes.
                foreach ($result[0] as $one) {
                    $q = str_replace($one, '', $q);
                    $querylist[] = "d.content like :q" . $i;
                    $params["q" . $i] = '%' . trim($one, '"') . '%';
                    $i++;
                }
            }

            $words = explode(' ', $q);

            foreach ($words as $word) {

                $word = trim($word);

                // Exclude short words.
                if (strlen($word) < 3) {
                    continue;
                }

                // Exclude stop words.
                $stop_words = self::stop_words();

                if (in_array($word, $stop_words)) {
                    continue;
                }

                $querylist[] = "d.content like :q" . $i;
                $params["q" . $i] = '%' . $word . '%';
                $i++;
            }

            if (count($querylist) > 0) {
                $query  = '(' . implode(' OR ', $querylist) . ')';
            }
        }

        $filterkeys = array();
        foreach ($filters as $m) {
            $key = trim($m['key']);
            if (!is_numeric($key)) {
                continue;
            }
            $key = intval($key);

            if (!key_exists($key, $filterkeys)) {
                $filterkeys[$key] = array();
            }
            $filterkeys[$key][] = $m['value'];
        }

        $queryfilters = array();

        foreach ($filterkeys as $k => $m) {

            $specificfilter = array();
            foreach ($m as $value) {
                $specificfilter[] = "(d.fieldid = :f" . $i . " AND d.content LIKE :q" . $i . ")";
                $params["f" . $i] = $k;
                $params["q" . $i] = '%' . $value . '%';
                $i++;
            }

            $queryfilters[] = '(' . implode(' OR ', $specificfilter) . ')';
        }

        $requiredfilters = null;
        if (!empty($dataview->customfilters)) {
            $customfilters = explode("\n", $dataview->customfilters);

            $requiredfilters = array();
            foreach ($customfilters as $line) {
                $parts = explode('|', $line);

                if (count($parts) != 2) {
                    continue;
                }

                $fieldname = trim($parts[0]);
                $fieldvalue = trim($parts[1]);

                // Values starting with # are a current user field filter.
                if (substr($fieldvalue, 0, 1) == '#') {
                    $f = ltrim($fieldvalue, '#');

                    if (!empty($f) && property_exists($USER, $f)) {
                        $fieldvalue = $USER->$f;
                    }
                }

                $datafield = $DB->get_record('data_fields', array('dataid' => $dataid, 'name' => $fieldname));

                // The field not exist for the current data module instance.
                if (!$datafield) {
                    continue;
                }

                $requiredfilters[] = "(id.fieldid = :f" . $i . " AND id.content LIKE :q" . $i . ")";
                $params["f" . $i] = $datafield->id;
                $params["q" . $i] = $fieldvalue;
                $i++;

            }

            $requiredfilters = implode(' AND ', $requiredfilters);

        }

        // ToDo: Separar la consulta por palabra clave de los filtros porque si se hacen al tiempo sobre campos
        // difetentes se anulan mutuamente y no se obtienen resultados.
        $query = !empty($query) ? $query . ' AND ' : '';
        if (count($queryfilters) > 0) {
            $query .= '(' . implode(' AND ', $queryfilters) . ')';
            $query = 'r.approved = 1 AND ' . $query;
        } else {
            $query .= 'r.approved = 1';
        }

        if ($requiredfilters) {
            $query .= " AND d.recordid IN (
                                SELECT recordid FROM {data_content} AS id
                                    INNER JOIN {data_records} AS ir ON ir.id = id.recordid AND ir.dataid = $dataid
                                    WHERE $requiredfilters
                                    )";
        }

        if (!empty($query)) {
            $query = ' WHERE ' . $query;
        }

        $sql = "SELECT DISTINCT d.recordid
                FROM {data_content} AS d
                INNER JOIN {data_records} AS r ON r.id = d.recordid AND r.dataid = $dataid"
                . $query;

        $records = $DB->get_records_sql($sql, $params, $start, $limit);

        $list = array();
        if (count($records) > 0) {

            require_once($CFG->dirroot . '/mod/data/lib.php');

            $fields = $DB->get_records('data_fields', array('dataid' => $dataview->dataid));
            $data = $DB->get_record('data', array('id' => $dataview->dataid), '*', MUST_EXIST);
            $cm = get_coursemodule_from_instance('data', $dataview->dataid, 0, false, MUST_EXIST);
            $context = context_module::instance($cm->id);
            require_capability('mod/dataview:view', $context);

            global $PAGE;
            $PAGE->set_context($context);

            foreach ($records as $record) {

                $one = new stdClass();
                foreach ($fields as $field) {
                    require_once($CFG->dirroot . '/mod/data/field/' . $field->type . '/field.class.php');

                    $displayfield = 'data_field_' . $field->type;
                    $displayfield = new $displayfield($field, $data, $cm);

                    $fieldname = $field->name;
                    $one->{$fieldname} = $displayfield->display_browse_field($record->recordid, 'listtemplate');
                }

                $list[] = json_encode($one);

//                $dr = $DB->get_records('data_content', array('recordid' => $record->recordid));
                // foreach ($dr as $singlerecord) {

                //     if (!isset($fields[$singlerecord->fieldid])) {
                //         continue;
                //     }

                //     $field = $fields[$singlerecord->fieldid];

                //     $displayfield = 'data_field_' . $field->type;
                //     $displayfield = new $displayfield($field, $data, $cm);

                //     $one->{$field} = $displayfield->display_browse_field($record->recordid);

                // }

                // $list[] = json_encode($one);
            }

        }

        return $list;
    }

    /**
     * Validate the return value
     * @return external_single_structure
     */
    public static function query_returns() {

        return new external_multiple_structure(
            new external_value(PARAM_RAW, 'List with records as JSON'), 'Records list', VALUE_DEFAULT, array()
        );
    }

    /**
     * List of stop words.
     *
     * @return array
     */
    public static function stop_words() {

        $jsonwords = get_string('stopwords', 'mod_dataview');

        return json_decode($jsonwords);

    }

}
