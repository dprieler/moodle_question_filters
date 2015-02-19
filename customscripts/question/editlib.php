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
 * Edit lib question
 *
 * @package    moodlecore
 * @subpackage questionbank
 * @copyright  1999 onwards Martin Dougiamas {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_question_bank_view extends question_bank_view {
    protected function known_field_types() {
        return array_merge(parent::known_field_types(),
            array(
                new custom_question_bank_question_defaultmark_column($this),
                new custom_question_bank_question_meta_field1_column($this),
            )
        );
    }

    protected function wanted_columns() {
        $columns = parent::wanted_columns();
        $columns[] = 'defaultmark';
        $columns[] = 'meta_field1';
        return $columns;
    }

    protected function display_question_list($contexts, $pageurl, $categoryandcontext,
            $cm = null, $recurse=1, $page=0, $perpage=100, $showhidden=false,
            $showquestiontext = false, $addcontexts = array()) {

        ob_start();
        $ret = parent::display_question_list($contexts, $pageurl, $categoryandcontext,
            $cm, $recurse, $page, $perpage, $showhidden,
            $showquestiontext, $addcontexts);
        $output = ob_get_clean();
        echo str_replace('<div class="categoryquestionscontainer">', '<div class="categoryquestionscontainer">'.
            'Number of Questions found: '.$this->get_question_count(), $output);
        return $ret;
    }
}




/**
 * A column type for the name of the question name.
 *
 * @copyright  2009 Tim Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_question_bank_question_defaultmark_column extends question_bank_column_base {
    protected $checkboxespresent = null;

    public function get_name() {
        return 'defaultmark';
    }

    protected function get_title() {
        return get_string('defaultmark', 'question');
    }

    protected function display_content($question, $rowclasses) {
        echo '<div style="text-align:right;">'.clean_param($question->defaultmark, PARAM_FLOAT).'</div>';
    }

    public function get_required_fields() {
        return array('q.defaultmark');
    }

    public function is_sortable() {
        return 'q.defaultmark';
    }
}

/**
 * A column type for the name of the question name.
 *
 * @copyright  2009 Tim Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_question_bank_question_meta_field1_column extends question_bank_row_base {
    protected $checkboxespresent = null;

    public function get_name() {
        return 'meta_field1';
    }

    protected function get_title() {
        return 'Metadatenfeld';
    }

    protected function display_content($question, $rowclasses) {
        $extrafields = local_question_filters_get_question_extra_fields($question->id);
        if ($extrafields && ($f = trim(clean_param($extrafields->meta_field1, PARAM_TEXT)))) {
            echo 'Metadatenfeld: '.$f;
        }
    }

    public function get_required_fields() {
        return array('q.defaultmark');
    }
}

