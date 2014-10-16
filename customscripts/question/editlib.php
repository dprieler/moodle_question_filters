<?php

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
		$extra_fields = local_question_filters_get_question_extra_fields($question->id);
		if ($extra_fields && ($f = trim(clean_param($extra_fields->meta_field1, PARAM_TEXT)))) {
			echo 'Metadatenfeld: '.$f;
		}
    }

    public function get_required_fields() {
        return array('q.defaultmark');
    }
}

