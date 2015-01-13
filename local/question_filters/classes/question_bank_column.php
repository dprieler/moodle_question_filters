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
defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->dirroot . '/question/editlib.php');

echo 'asdfaf'; exit;
class local_filter_questions_question_bank_column extends \core_question\bank\column_base {
    public function get_name() {
        return 'local_searchbytags|lastmodified';
    }

    protected function get_title() {
        return 'Last modified date';
    }

    protected function display_content($question, $rowclasses) {
        if (!empty($question->timemodified)) {
            echo date('M j y G:i', $question->timemodified);
        }
    }

    public function get_extra_joins() {
        return array();
    }

    public function get_required_fields() {
        // return array("(SELECT {tag}.name + ',' FROM {tag} WHERE id=tagi.tagid FOR XML PATH('')) AS tags");

        // for mssql
        /* */
            return array('q.timemodified');
        // For MySQL
        /*
        return array("
            (SELECT GROUP_CONCAT(name) AS tags FROM mdl_tag_instance LEFT JOIN mdl_tag ON mdl_tag.id=mdl_tag_instance.tagid WHERE itemid=q.id) as tags
        ");
        */
    }

    public function is_sortable() {
        return 'q.timemodified';
        // return array('timemodified' => array('field' => 'q.timemodified', 'title' =>  $this->get_title()) );
    }
}