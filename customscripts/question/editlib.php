<?php

class custom_question_bank_view extends question_bank_view {
    protected function known_field_types() {
        return array_merge(parent::known_field_types(),
			array(
				new custom_question_bank_question_defaultmark_column($this),
			)
		);
	}

    protected function wanted_columns() {
		$columns = parent::wanted_columns();
		$columns[] = 'defaultmark';
		return $columns;
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

