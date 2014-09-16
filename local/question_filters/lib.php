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

function local_question_filters_get_filter_sql(&$params, &$where, stdClass $filter = null, $sql_paramnames = true, $sql_prefix = 'q.') {
	global $DB;
	
	if ($filter === null) {
		$filter = (object)array(
			'filter_name' => trim(optional_param('filter_name', '', PARAM_TEXT)),
			'filter_questiontext' => trim(optional_param('filter_questiontext', '', PARAM_TEXT)),
			// empty field -> null, anything else -> convert to integer
			'filter_defaultmark' => trim(optional_param('filter_defaultmark', '', PARAM_TEXT)) !== '' ? optional_param('filter_defaultmark', 0, PARAM_INT) : null,
			'filter_defaultmark_search' => optional_param('filter_defaultmark_search', null, PARAM_RAW),
		);
	} else {
		if (!$filter->filter_defaultmark_search || $filter->filter_defaultmark == '') {
			$filter->filter_defaultmark = null;
		}
	}

	if (!in_array($filter->filter_defaultmark_search, array('>' => '>', '>=' => '>=', '=' => '=', '<=' => '<=', '<' => '<'))) {
		$filter->filter_defaultmark_search = '=';
	}
	
	if (!$filter->filter_name && !$filter->filter_questiontext && $filter->filter_defaultmark === null) {
		// no filtering
		if (!is_array($where) && empty($where))
			$where = '1=1';
		
		return;
	}
	
	$addwhere = '(1=1';
	if ($filter->filter_name) {
		$params['filter_name'] = '%'.$filter->filter_name.'%';
		$addwhere .= ' AND '.$DB->sql_like($sql_prefix.'name', (!$sql_paramnames ? '?' : ':filter_name'), false);
	}
	if ($filter->filter_questiontext) {
		$params['filter_questiontext'] = '%'.$filter->filter_questiontext.'%';
		$addwhere .= ' AND '.$DB->sql_like($sql_prefix.'questiontext', (!$sql_paramnames ? '?' : ':filter_questiontext'), false);
	}
	if ($filter->filter_defaultmark !== null) {
		$params['filter_defaultmark'] = $filter->filter_defaultmark;
		$addwhere .= ' AND '.$sql_prefix."defaultmark ".$filter->filter_defaultmark_search." ".(!$sql_paramnames ? '?' : ":filter_defaultmark");
	}
	$addwhere .= ')';

	if (is_array($where))
		$where[] = $addwhere;
	elseif (is_string($where)) {
		if ($where) $where .= ' AND ';
		$where .= $addwhere;
	}
	else
		die('error wrong where');
	
	// filtered
	return true;
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
		
		local_question_filters_get_filter_sql($this->params, $this->where);
	}
}


