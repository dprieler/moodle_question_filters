<?php

require_once($CFG->dirroot . '/mod/quiz/addrandomform.php');
require_once($CFG->dirroot . '/local/question_filters/lib.php');

class custom_quiz_add_random_form extends quiz_add_random_form {
    protected function definition() {

        global $CFG, $DB;
        $mform =& $this->_form;

		$mform->addElement('header', 'categoryheader', 'Special Filters');
		$mform->addElement('text', 'filter_name', 'Fragetitel', 'maxlength="254" size="50"');
        $mform->setType('filter_name', PARAM_TEXT);
        $mform->addElement('text', 'filter_questiontext', 'Fragetext', 'maxlength="254" size="50"');
        $mform->setType('filter_questiontext', PARAM_TEXT);
        $mform->addElement('text', 'filter_meta_field1', 'Metadatenfeld', 'maxlength="254" size="50"');
        $mform->setType('filter_meta_field1', PARAM_TEXT);
        $mform->addElement('select', 'filter_defaultmark_search', 'Punktezahl Filter', array('>' => '>', '>=' => '>=', '=' => '=', '<=' => '<=', '<' => '<'));
        $mform->addElement('text', 'filter_defaultmark', 'Punktezahl', 'maxlength="254" size="50"');
        $mform->setType('filter_defaultmark', PARAM_TEXT);

		parent::definition();
	}
}

function custom_quiz_add_random_questions($quiz, $addonpage, $categoryid, $number,
        $includesubcategories) {
    global $DB;
	
    $category = $DB->get_record('question_categories', array('id' => $categoryid));
    if (!$category) {
        print_error('invalidcategoryid', 'error');
    }

    $catcontext = context::instance_by_id($category->contextid);
    require_capability('moodle/question:useall', $catcontext);

	$recheckQuestions = array();
	
    // Find existing random questions in this category that are
    // not used by any quiz.
    if ($existingquestions = $DB->get_records_sql(
            "SELECT q.id, q.qtype FROM {question} q
            WHERE qtype = 'random'
                AND category = ?
                AND " . $DB->sql_compare_text('questiontext') . " = ?
                AND NOT EXISTS (
                        SELECT *
                          FROM {quiz_slots}
                         WHERE questionid = q.id)
            ORDER BY id", array($category->id, ($includesubcategories ? '1' : '0')))) {
        // Take as many of these as needed.
        while (($existingquestion = array_shift($existingquestions)) && $number > 0) {
			quiz_add_quiz_question($existingquestion->id, $quiz, $addonpage);
            $recheckQuestions[] = $existingquestion;
            $number -= 1;
        }
    }

    if ($number <= 0) {
        return;
    }

    // More random questions are needed, create them.
    for ($i = 0; $i < $number; $i += 1) {
        $form = new stdClass();
        $form->questiontext = array('text' => ($includesubcategories ? '1' : '0'), 'format' => 0);
        $form->category = $category->id . ',' . $category->contextid;
        $form->defaultmark = 1;
        $form->hidden = 1;
        $form->stamp = make_unique_id_code(); // Set the unique code (not to be changed).
        $question = new stdClass();
        $question->qtype = 'random';
        $question = question_bank::get_qtype('random')->save_question($question, $form);
        if (!isset($question->id)) {
            print_error('cannotinsertrandomquestion', 'quiz');
        }
        quiz_add_quiz_question($question->id, $quiz, $addonpage);
		$recheckQuestions[] = $question;
    }
	
	foreach ($recheckQuestions as $question) {
		$filter = local_question_filters_get_filter_from_form();
		$filter->questionid = $question->id;

		local_question_filters_save_question_extra_fields($filter);
	}
}

/*
function custom_get_available_questions_from_category_with_filter($categoryid, $subcategories, $filter) {
	global $DB;

	if ($subcategories) {
		$categoryids = question_categorylist($categoryid);
	} else {
		$categoryids = array($categoryid);
	}

	$params = array();
	$where = array('1 = 1');

	local_question_filters_get_filter_sql($params, $where, $filter, true, false);

	$questionids = question_bank::get_finder()->get_questions_from_categories(
			$categoryids, 'qtype NOT IN (' . "'description','missingtype','random','randomsamatch'" . ') AND '.join(' and ', $where), $params);

	return $questionids;
}
*/