<?php

class custom_quiz_add_random_form extends quiz_add_random_form {
    protected function definition() {

        global $CFG, $DB;
        $mform =& $this->_form;

		$mform->addElement('header', 'categoryheader', 'Special Filters');

		$mform->addElement('text', 'filter_name', 'Fragetitel', 'maxlength="254" size="50"');
        $mform->setType('filter_name', PARAM_TEXT);
        $mform->addElement('text', 'filter_questiontext', 'Fragetext', 'maxlength="254" size="50"');
        $mform->setType('filter_questiontext', PARAM_TEXT);
        $mform->addElement('select', 'filter_defaultmark_search', 'Punktezahl Filter', array('>' => '>', '>=' => '>=', '=' => '=', '<=' => '<=', '<' => '<'));
        $mform->addElement('text', 'filter_defaultmark', 'Punktezahl', 'maxlength="254" size="50"');
        $mform->setType('filter_defaultmark', PARAM_TEXT);

		parent::definition();
	}
}
