<?php

require_once($CFG->dirroot . '/mod/quiz/addrandomform.php');

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
		$form = new stdClass();
		$form->id = $question->id;

		if ($q = trim(optional_param('filter_name', null, PARAM_TEXT))) {
			$form->filter_name = $q;
		} else $form->filter_name = '';
		if ($q = trim(optional_param('filter_questiontext', null, PARAM_TEXT))) {
			$form->filter_questiontext = $q;
		} else $form->filter_questiontext = '';
		if ($q = trim(optional_param('filter_defaultmark', null, PARAM_INT))) {
			$defaultmark_search = optional_param('filter_defaultmark_search', null, PARAM_RAW);
			if (!in_array($defaultmark_search, array('>' => '>', '>=' => '>=', '=' => '=', '<=' => '<=', '<' => '<'))) {
				$defaultmark_search = '=';
			}
			$form->filter_defaultmark = $q;
			$form->filter_defaultmark_search = $defaultmark_search;
		} else {
			$form->filter_defaultmark = '';
			$form->filter_defaultmark_search = '';
		}
		
		$DB->update_record('question', $form);
	}
}



/**
 * Prints a list of quiz questions for the edit.php main view for edit
 * ($reordertool = false) and order and paging ($reordertool = true) tabs
 *
 * @param object $quiz The quiz settings.
 * @param moodle_url $pageurl The url of the current page with the parameters required
 *     for links returning to the current page, as a moodle_url object
 * @param bool $allowdelete Indicates whether the delete icons should be displayed
 * @param bool $reordertool  Indicates whether the reorder tool should be displayed
 * @param bool $quiz_qbanktool  Indicates whether the question bank should be displayed
 * @param bool $hasattempts  Indicates whether the quiz has attempts
 * @param object $defaultcategoryobj
 * @param bool $canaddquestion is the user able to add and use questions anywere?
 * @param bool $canaddrandom is the user able to add random questions anywere?
 */
function custom_quiz_print_question_list($quiz, $pageurl, $allowdelete, $reordertool,
        $quiz_qbanktool, $hasattempts, $defaultcategoryobj, $canaddquestion, $canaddrandom) {
    global $CFG, $DB, $OUTPUT;
    $strorder = get_string('order');
    $strquestionname = get_string('questionname', 'quiz');
    $strmaxmark = get_string('markedoutof', 'question');
    $strremove = get_string('remove', 'quiz');
    $stredit = get_string('edit');
    $strview = get_string('view');
    $straction = get_string('action');
    $strmove = get_string('move');
    $strmoveup = get_string('moveup');
    $strmovedown = get_string('movedown');
    $strsave = get_string('save', 'quiz');
    $strreorderquestions = get_string('reorderquestions', 'quiz');

    $strselectall = get_string('selectall', 'quiz');
    $strselectnone = get_string('selectnone', 'quiz');
    $strtype = get_string('type', 'quiz');
    $strpreview = get_string('preview', 'quiz');

    $questions = $DB->get_records_sql("SELECT slot.slot, q.*, qc.contextid, slot.page, slot.maxmark
                          FROM {quiz_slots} slot
                     LEFT JOIN {question} q ON q.id = slot.questionid
                     LEFT JOIN {question_categories} qc ON qc.id = q.category
                         WHERE slot.quizid = ?
                      ORDER BY slot.slot", array($quiz->id));

    $lastindex = count($questions) - 1;

    $disabled = '';
    $pagingdisabled = '';
    if ($hasattempts) {
        $disabled = 'disabled="disabled"';
    }
    if ($hasattempts || $quiz->shufflequestions) {
        $pagingdisabled = 'disabled="disabled"';
    }

    $reordercontrolssetdefaultsubmit = '<div style="display:none;">' .
        '<input type="submit" name="savechanges" value="' .
        $strreorderquestions . '" ' . $pagingdisabled . ' /></div>';
    $reordercontrols1 = '<div class="addnewpagesafterselected">' .
        '<input type="submit" name="addnewpagesafterselected" value="' .
        get_string('addnewpagesafterselected', 'quiz') . '"  ' .
        $pagingdisabled . ' /></div>';
    $reordercontrols1 .= '<div class="quizdeleteselected">' .
        '<input type="submit" name="quizdeleteselected" ' .
        'onclick="return confirm(\'' .
        get_string('areyousureremoveselected', 'quiz') . '\');" value="' .
        get_string('removeselected', 'quiz') . '"  ' . $disabled . ' /></div>';

    $a = '<input name="moveselectedonpagetop" type="text" size="2" ' .
        $pagingdisabled . ' />';
    $b = '<input name="moveselectedonpagebottom" type="text" size="2" ' .
        $pagingdisabled . ' />';

    $reordercontrols2top = '<div class="moveselectedonpage">' .
        '<label>' . get_string('moveselectedonpage', 'quiz', $a) . '</label>' .
        '<input type="submit" name="savechanges" value="' .
        $strmove . '"  ' . $pagingdisabled . ' />' . '
        <br /><input type="submit" name="savechanges" value="' .
        $strreorderquestions . '" /></div>';
    $reordercontrols2bottom = '<div class="moveselectedonpage">' .
        '<input type="submit" name="savechanges" value="' .
        $strreorderquestions . '" /><br />' .
        '<label>' . get_string('moveselectedonpage', 'quiz', $b) . '</label>' .
        '<input type="submit" name="savechanges" value="' .
        $strmove . '"  ' . $pagingdisabled . ' /> ' . '</div>';

    $reordercontrols3 = '<a href="javascript:select_all_in(\'FORM\', null, ' .
            '\'quizquestions\');">' .
            $strselectall . '</a> /';
    $reordercontrols3.=    ' <a href="javascript:deselect_all_in(\'FORM\', ' .
            'null, \'quizquestions\');">' .
            $strselectnone . '</a>';

    $reordercontrolstop = '<div class="reordercontrols">' .
            $reordercontrolssetdefaultsubmit .
            $reordercontrols1 . $reordercontrols2top . $reordercontrols3 . "</div>";
    $reordercontrolsbottom = '<div class="reordercontrols">' .
            $reordercontrolssetdefaultsubmit .
            $reordercontrols2bottom . $reordercontrols1 . $reordercontrols3 . "</div>";

    if ($reordertool) {
        echo '<form method="post" action="edit.php" id="quizquestions"><div>';

        echo html_writer::input_hidden_params($pageurl);
        echo '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';

        echo $reordercontrolstop;
    }

    // Build fake order for backwards compatibility.
    $currentpage = 1;
    $order = array();
    foreach ($questions as $question) {
        while ($question->page > $currentpage) {
            $currentpage += 1;
            $order[] = 0;
        }
        $order[] = $question->slot;
    }
    $order[] = 0;

    // The current question ordinal (no descriptions).
    $qno = 1;
    // The current question (includes questions and descriptions).
    $questioncount = 0;
    // The current page number in iteration.
    $pagecount = 0;

    $pageopen = false;
    $lastslot = 0;

    $returnurl = $pageurl->out_as_local_url(false);
    $questiontotalcount = count($order);

    $lastquestion = new stdClass();
    $lastquestion->slot = 0; // Used to get the add page here buttons right.
    foreach ($order as $count => $qnum) { // Note: $qnum is acutally slot number, if it is not 0.

        $reordercheckbox = '';
        $reordercheckboxlabel = '';
        $reordercheckboxlabelclose = '';

        // If the questiontype is missing change the question type.
        if ($qnum && $questions[$qnum]->qtype === null) {
            $questions[$qnum]->id = $qnum;
            $questions[$qnum]->category = 0;
            $questions[$qnum]->qtype = 'missingtype';
            $questions[$qnum]->name = get_string('missingquestion', 'quiz');
            $questions[$qnum]->questiontext = ' ';
            $questions[$qnum]->questiontextformat = FORMAT_HTML;
            $questions[$qnum]->length = 1;

        } else if ($qnum && !question_bank::qtype_exists($questions[$qnum]->qtype)) {
            $questions[$qnum]->qtype = 'missingtype';
        }

        if ($qnum != 0 || ($qnum == 0 && !$pageopen)) {
            // This is either a question or a page break after another (no page is currently open).
            if (!$pageopen) {
                // If no page is open, start display of a page.
                $pagecount++;
                echo  '<div class="quizpage"><span class="pagetitle">' .
                        get_string('page') . '&nbsp;' . $pagecount .
                        '</span><div class="pagecontent">';
                $pageopen = true;
            }
            if ($qnum == 0) {
                // This is the second successive page break. Tell the user the page is empty.
                echo '<div class="pagestatus">';
                print_string('noquestionsonpage', 'quiz');
                echo '</div>';
                if ($allowdelete) {
                    echo '<div class="quizpagedelete">';
                    echo $OUTPUT->action_icon($pageurl->out(true,
                            array('deleteemptypage' => $pagecount, 'sesskey' => sesskey())),
                            new pix_icon('t/delete', $strremove),
                            new component_action('click',
                                    'M.core_scroll_manager.save_scroll_action'),
                            array('title' => $strremove));
                    echo '</div>';
                }
            }

            if ($qnum != 0) {
                $question = $questions[$qnum];
                $questionparams = array(
                        'returnurl' => $returnurl,
                        'cmid' => $quiz->cmid,
                        'id' => $question->id);
                $questionurl = new moodle_url('/question/question.php',
                        $questionparams);
                $questioncount++;

                // This is an actual question.
                ?>
<div class="question">
    <div class="questioncontainer <?php echo $question->qtype; ?>">
        <div class="qnum">
                <?php
                $reordercheckbox = '';
                $reordercheckboxlabel = '';
                $reordercheckboxlabelclose = '';
                if ($reordertool) {
                    $reordercheckbox = '<input type="checkbox" name="s' . $question->slot .
                        '" id="s' . $question->slot . '" />';
                    $reordercheckboxlabel = '<label for="s' . $question->slot . '">';
                    $reordercheckboxlabelclose = '</label>';
                }
                if ($question->length == 0) {
                    $qnodisplay = get_string('infoshort', 'quiz');
                } else if ($quiz->shufflequestions) {
                    $qnodisplay = '?';
                } else {
                    if ($qno > 999 || ($reordertool && $qno > 99)) {
                        $qnodisplay = html_writer::tag('small', $qno);
                    } else {
                        $qnodisplay = $qno;
                    }
                    $qno += $question->length;
                }
                echo $reordercheckboxlabel . $qnodisplay . $reordercheckboxlabelclose .
                        $reordercheckbox;

                ?>
        </div>
        <div class="content">
            <div class="questioncontrols">
                <?php
                if ($count != 0) {
                    if (!$hasattempts) {
                        $upbuttonclass = '';
                        echo $OUTPUT->action_icon($pageurl->out(true,
                                array('up' => $question->slot, 'sesskey' => sesskey())),
                                new pix_icon('t/up', $strmoveup),
                                new component_action('click',
                                        'M.core_scroll_manager.save_scroll_action'),
                                array('title' => $strmoveup));
                    }

                }
                if (!$hasattempts) {
                    echo $OUTPUT->action_icon($pageurl->out(true,
                            array('down' => $question->slot, 'sesskey' => sesskey())),
                            new pix_icon('t/down', $strmovedown),
                            new component_action('click',
                                    'M.core_scroll_manager.save_scroll_action'),
                            array('title' => $strmovedown));
                }
                if ($allowdelete && ($question->qtype == 'missingtype' ||
                        question_has_capability_on($question, 'use', $question->category))) {
                    // Remove from quiz, not question delete.
                    if (!$hasattempts) {
                        echo $OUTPUT->action_icon($pageurl->out(true,
                                array('remove' => $question->slot, 'sesskey' => sesskey())),
                                new pix_icon('t/delete', $strremove),
                                new component_action('click',
                                        'M.core_scroll_manager.save_scroll_action'),
                                array('title' => $strremove));
                    }
                }
                ?>
            </div><?php
                if (!in_array($question->qtype, array('description', 'missingtype')) && !$reordertool) {
                    ?>
<div class="points">
<form method="post" action="edit.php" class="quizsavegradesform"><div>
    <fieldset class="invisiblefieldset" style="display: block;">
    <label for="<?php echo 'inputq' . $question->slot; ?>"><?php echo $strmaxmark; ?></label>:<br />
    <input type="hidden" name="sesskey" value="<?php echo sesskey() ?>" />
    <?php echo html_writer::input_hidden_params($pageurl); ?>
    <input type="hidden" name="savechanges" value="save" />
                    <?php
                    echo '<input type="text" name="g' . $question->slot .
                            '" id="inputq' . $question->slot .
                            '" size="' . ($quiz->decimalpoints + 2) .
                            '" value="' . (0 + $question->maxmark) .
                            '" tabindex="' . ($lastindex + $qno) . '" />';
                    ?>
        <input type="submit" class="pointssubmitbutton" value="<?php echo $strsave; ?>" />
    </fieldset>
                    <?php
                    if ($question->qtype == 'random') {
                        echo '<a href="' . $questionurl->out() .
                                '" class="configurerandomquestion">' .
                                get_string("configurerandomquestion", "quiz") . '</a>';
                    }

                    ?>
</div>
</form>

            </div>
                    <?php
                } else if ($reordertool) {
                    if ($qnum) {
                        ?>
<div class="qorder">
                        <?php
                        echo '<label class="accesshide" for="o' . $question->slot . '">' .
                                get_string('questionposition', 'quiz', $qnodisplay) . '</label>';
                        echo '<input type="text" name="o' . $question->slot .
                                '" id="o' . $question->id . '"' .
                                '" size="2" value="' . (10*$count + 10) .
                                '" tabindex="' . ($lastindex + $qno) . '" />';
                        ?>
</div>
                        <?php
                    }
                }
                ?>
            <div class="questioncontentcontainer">
                <?php
                if ($question->qtype == 'random') { // It is a random question.
                    if (!$reordertool) {
                        custom_quiz_print_randomquestion($question, $pageurl, $quiz, $quiz_qbanktool);
                    } else {
                        quiz_print_randomquestion_reordertool($question, $pageurl, $quiz);
                    }
                } else { // It is a single question.
                    if (!$reordertool) {
                        quiz_print_singlequestion($question, $returnurl, $quiz);
                    } else {
                        quiz_print_singlequestion_reordertool($question, $returnurl, $quiz);
                    }
                }
                ?>
            </div>
        </div>
    </div>
</div>

                <?php
            }
        }
        // A page break: end the existing page.
        if ($qnum == 0) {
            if ($pageopen) {
                if (!$reordertool && !($quiz->shufflequestions &&
                        $count < $questiontotalcount - 1)) {
                    quiz_print_pagecontrols($quiz, $pageurl, $pagecount,
                            $hasattempts, $defaultcategoryobj, $canaddquestion, $canaddrandom);
                } else if ($count < $questiontotalcount - 1) {
                    // Do not include the last page break for reordering
                    // to avoid creating a new extra page in the end.
                    echo '<input type="hidden" name="opg' . $pagecount . '" size="2" value="' .
                            (10*$count + 10) . '" />';
                }
                echo "</div></div>";

                if (!$reordertool && !$quiz->shufflequestions && $count < $questiontotalcount - 1) {
                    echo $OUTPUT->container_start('addpage');
                    $url = new moodle_url($pageurl->out_omit_querystring(),
                            array('cmid' => $quiz->cmid, 'courseid' => $quiz->course,
                                    'addpage' => $lastquestion->slot, 'sesskey' => sesskey()));
                    echo $OUTPUT->single_button($url, get_string('addpagehere', 'quiz'), 'post',
                            array('disabled' => $hasattempts,
                            'actions' => array(new component_action('click',
                                    'M.core_scroll_manager.save_scroll_action'))));
                    echo $OUTPUT->container_end();
                }
                $pageopen = false;
                $count++;
            }
        }

        if ($qnum != 0) {
            $lastquestion = $question;
        }

    }
    if ($reordertool) {
        echo $reordercontrolsbottom;
        echo '</div></form>';
    }
}



/**
 * Print a given random question in quiz for the edit tab of edit.php.
 * Meant to be used from quiz_print_question_list()
 *
 * @param object $question A question object from the database questions table
 * @param object $questionurl The url of the question editing page as a moodle_url object
 * @param object $quiz The quiz in the context of which the question is being displayed
 * @param bool $quiz_qbanktool Indicate to this function if the question bank window open
 */
function custom_quiz_print_randomquestion($question, $pageurl, $quiz, $quiz_qbanktool) {
    global $DB, $OUTPUT;
    echo '<div class="quiz_randomquestion">';

    if (!$category = $DB->get_record('question_categories',
            array('id' => $question->category))) {
        echo $OUTPUT->notification('Random question category not found!');
        return;
    }

    echo '<div class="randomquestionfromcategory">';
    echo print_question_icon($question);
    print_random_option_icon($question);
    echo ' ' . get_string('randomfromcategory', 'quiz') . '</div>';

    $a = new stdClass();
    $a->arrow = $OUTPUT->rarrow();
    $strshowcategorycontents = get_string('showcategorycontents', 'quiz', $a);

    $openqbankurl = $pageurl->out(true, array('qbanktool' => 1,
            'cat' => $category->id . ',' . $category->contextid));
    $linkcategorycontents = ' <a href="' . $openqbankurl . '">' . $strshowcategorycontents . '</a>';

    echo '<div class="randomquestioncategory">';
    echo '<a href="' . $openqbankurl . '" title="' . $strshowcategorycontents . '">' .
            $category->name . '</a>';
    echo '<span class="questionpreview">' .
            quiz_question_preview_button($quiz, $question, true) . '</span>';
    echo '</div>';

    $questionids = custom_get_available_questions_from_category_with_filter(
            $category->id, $question->questiontext == '1', $question);
    $questioncount = count($questionids);

	echo '<div>';
	if ($question->filter_name || $question->filter_questiontext || $question->filter_defaultmark) {
		echo '<b>Filters:</b>'.'<br>';
		if ($question->filter_name)
			echo 'Name: '.$question->filter_name.'<br>';
		if ($question->filter_questiontext)
			echo 'Text: '.$question->filter_questiontext.'<br>';
		if ($question->filter_defaultmark)
			echo 'Mark: '.$question->filter_defaultmark_search.' '.$question->filter_defaultmark.'<br>';
		echo '</div>';
	}
    echo '<div class="randomquestionqlist">';
    if ($questioncount == 0) {
        // No questions in category, give an error plus instructions.
        echo '<span class="error">';
        print_string('noquestionsnotinuse', 'quiz');
        echo '</span>';
        echo '<br />';

        // Embed the link into the string with instructions.
        $a = new stdClass();
        $a->catname = '<strong>' . $category->name . '</strong>';
        $a->link = $linkcategorycontents;
        echo get_string('addnewquestionsqbank', 'quiz', $a);

    } else {
        // Category has questions.

        // Get a sample from the database.
        $questionidstoshow = array_slice($questionids, 0, NUM_QS_TO_SHOW_IN_RANDOM);
        $questionstoshow = $DB->get_records_list('question', 'id', $questionidstoshow,
                '', 'id, qtype, name, questiontext, questiontextformat');

        // Then list them.
        echo '<ul>';
        foreach ($questionstoshow as $subquestion) {
            echo '<li>' . quiz_question_tostring($subquestion, true) . '</li>';
        }

        // Finally display the total number.
        echo '<li class="totalquestionsinrandomqcategory">';
        if ($questioncount > NUM_QS_TO_SHOW_IN_RANDOM) {
            echo '... ';
        }
        print_string('totalquestionsinrandomqcategory', 'quiz', $questioncount);
        echo ' ' . $linkcategorycontents;
        echo '</li>';
        echo '</ul>';
    }

    echo '</div>';
    echo '<div class="randomquestioncategorycount">';
    echo '</div>';
    echo '</div>';
}

function custom_get_available_questions_from_category_with_filter($categoryid, $subcategories, $filter) {
	global $DB;
	
	if ($subcategories) {
		$categoryids = question_categorylist($categoryid);
	} else {
		$categoryids = array($categoryid);
	}

	$params = array();
	$where = array('1 = 1');

	if ($q = $filter->filter_name) {
		$params['filter_name'] = '%'.$q.'%';
		$where[] = $DB->sql_like('name', ':filter_name', false);
	}
	if ($q = $filter->filter_questiontext) {
		$params['questiontext'] = '%'.$q.'%';
		$where[] = $DB->sql_like('questiontext', ':questiontext', false);
	}
	if ($q = $filter->filter_defaultmark) {
		$defaultmark_search = $filter->filter_defaultmark_search;
		if (!in_array($defaultmark_search, array('>' => '>', '>=' => '>=', '=' => '=', '<=' => '<=', '<' => '<'))) {
			$defaultmark_search = '=';
		}
		$params['defaultmark'] = $q;
		$where[] = "defaultmark ".$defaultmark_search." :defaultmark";
	}
		
	$questionids = question_bank::get_finder()->get_questions_from_categories(
			$categoryids, 'qtype NOT IN (' . "'description','missingtype','random','randomsamatch'" . ') AND '.join(' and ', $where), $params);

	return $questionids;
}
	