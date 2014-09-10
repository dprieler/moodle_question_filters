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

require_once($CFG->libdir . '/outputcomponents.php');
require_once($CFG->dirroot . '/question/editlib.php');


function local_question_filters_get_question_bank_search_conditions() {
	return array(new local_question_filters_question_bank_search_condition());
}

function local_question_filters_question_bank_column_types($question_bank_view) {
	echo 'z'; exit;
    return array('lastmodified' => new local_question_filters_question_bank_column($question_bank_view));
}

class local_question_filters_question_bank_search_condition  extends \core_question\bank\search\condition  {
	protected $where = array();
	protected $params = array();

	public function __construct() {
		$this->init();
	}

	public function where() {
		return join(' AND ', $this->where);
	}

	public function params() {
		return $this->params;
	}

	public function display_options() {
		$return = '';
		$return .= html_writer::label('Fragetitel', 'filter_name');
		$return .= html_writer::empty_tag('input',
				array('name' => 'filter_name', 'id' => 'filter_name', 'class' => 'searchoptions', 'value' => optional_param('filter_name', null, PARAM_TEXT)));

		$return .= html_writer::label('Fragetext', 'filter_questiontext');
		$return .= html_writer::empty_tag('input',
				array('name' => 'filter_questiontext', 'id' => 'filter_questiontext', 'class' => 'searchoptions', 'value' => optional_param('filter_questiontext', null, PARAM_TEXT)));
		
		$return .= html_writer::label('Punktezahl', 'filter_defaultmark');
		$return .= html_writer::select(
				array('>' => '>', '>=' => '>=', '=' => '=', '<=' => '<=', '<' => '<'), 'filter_defaultmark_search', optional_param('filter_defaultmark_search', '=', PARAM_RAW), false);
		$return .= html_writer::empty_tag('input',
				array('name' => 'filter_defaultmark', 'id' => 'filter_defaultmark', 'class' => 'searchoptions', 'value' => optional_param('filter_defaultmark', null, PARAM_TEXT)));

		$return .= '<div>'.
			html_writer::empty_tag('input',
				array('type' => 'submit', 'value' => get_string('search')))
			.'</div>';
				
		return $return;

	}

	public function display_options_adv() {
		// return 'Advanced UI from search plugin here<br />';
	}

	private function init() {
		global $DB;
		
		if ($q = trim(optional_param('filter_name', null, PARAM_TEXT))) {
			$this->params['name'] = '%'.$q.'%';
			$this->where[] = $DB->sql_like('q.name', ':name', false);
		}
		if ($q = trim(optional_param('filter_questiontext', null, PARAM_TEXT))) {
			$this->params['questiontext'] = '%'.$q.'%';
			$this->where[] = $DB->sql_like('q.questiontext', ':questiontext', false);
		}
		if ($q = trim(optional_param('filter_defaultmark', null, PARAM_INT))) {
			$this->params['defaultmark'] = $q;
			$defaultmark_search = optional_param('filter_defaultmark_search', null, PARAM_RAW);
			if (!in_array($defaultmark_search, array('>' => '>', '>=' => '>=', '=' => '=', '<=' => '<=', '<' => '<'))) {
				$defaultmark_search = '=';
			}
			$this->where[] = "q.defaultmark ".$defaultmark_search." :defaultmark";
		}
	}
}


