<?php

namespace local_question_filters;

defined('MOODLE_INTERNAL') || die();

require_once __DIR__.'/../lib.php';

class lib {
	/**
	 * Get extra fields
	 *
	 * @param integer $questionid
	 * @return array
	 */
	static function get_question_extra_fields($questionid) {
		global $DB;
		return $DB->get_record('local_question_filters', array('questionid' => $questionid));
	}
}
