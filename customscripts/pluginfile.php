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
 * This script delegates file serving to individual plugins
 *
 * @package    core
 * @subpackage file
 * @copyright  2008 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Disable moodle specific debug messages and any errors in output.
#define('NO_DEBUG_DISPLAY', true); // disabled by G. Schwed

//require_once('config.php');
require_once('lib/filelib.php');

$relativepath = get_file_argument();
$forcedownload = optional_param('forcedownload', 0, PARAM_BOOL);
$preview = optional_param('preview', null, PARAM_ALPHANUM);

require_once($CFG->dirroot . '/question/format/xml/format.php');
require_once($CFG->dirroot . '/question/format/gift/format.php');
require_once($CFG->dirroot . '/local/question_filters/lib.php');

class custom_qformat_gift extends qformat_gift {
	// custom_qformat_gift:exportprocess and custom_qformat_xml:exportprocess are the same!
    public function exportprocess() {
        global $CFG, $OUTPUT, $DB, $USER;

        // get the questions (from database) in this category
        // only get q's with no parents (no cloze subquestions specifically)
        if ($this->category) {
            $questions = custom_get_questions_category($this->category, true);
        } else {
            $questions = $this->questions;
        }

        $count = 0;

        // results are first written into string (and then to a file)
        // so create/initialize the string here
        $expout = "";

        // track which category questions are in
        // if it changes we will record the category change in the output
        // file if selected. 0 means that it will get printed before the 1st question
        $trackcategory = 0;

        // iterate through questions
        foreach ($questions as $question) {
            // used by file api
            $contextid = $DB->get_field('question_categories', 'contextid',
                    array('id' => $question->category));
            $question->contextid = $contextid;

            // do not export hidden questions
            if (!empty($question->hidden)) {
                continue;
            }

            // do not export random questions
            if ($question->qtype == 'random') {
                continue;
            }

            // check if we need to record category change
            if ($this->cattofile) {
                if ($question->category != $trackcategory) {
                    $trackcategory = $question->category;
                    $categoryname = $this->get_category_path($trackcategory, $this->contexttofile);

                    // create 'dummy' question for category export
                    $dummyquestion = new stdClass();
                    $dummyquestion->qtype = 'category';
                    $dummyquestion->category = $categoryname;
                    $dummyquestion->name = 'Switch category to ' . $categoryname;
                    $dummyquestion->id = 0;
                    $dummyquestion->questiontextformat = '';
                    $dummyquestion->contextid = 0;
                    $expout .= $this->writequestion($dummyquestion) . "\n";
                }
            }

            // export the question displaying message
            $count++;

            if (question_has_capability_on($question, 'view', $question->category)) {
                $expout .= $this->writequestion($question, $contextid) . "\n";
            }
        }

        // continue path for following error checks
        $course = $this->course;
        $continuepath = "$CFG->wwwroot/question/export.php?courseid=$course->id";

        // did we actually process anything
        if ($count==0) {
            print_error('noquestions', 'question', $continuepath);
        }

        // final pre-process on exported data
        $expout = $this->presave_process($expout);
        return $expout;
    }
}

class custom_qformat_xml extends qformat_xml {
	// custom_qformat_gift:exportprocess and custom_qformat_xml:exportprocess are the same!
    public function exportprocess() {
        global $CFG, $OUTPUT, $DB, $USER;

        // get the questions (from database) in this category
        // only get q's with no parents (no cloze subquestions specifically)
        if ($this->category) {
            $questions = custom_get_questions_category($this->category, true);
        } else {
            $questions = $this->questions;
        }

        $count = 0;

        // results are first written into string (and then to a file)
        // so create/initialize the string here
        $expout = "";

        // track which category questions are in
        // if it changes we will record the category change in the output
        // file if selected. 0 means that it will get printed before the 1st question
        $trackcategory = 0;

        // iterate through questions
        foreach ($questions as $question) {
            // used by file api
            $contextid = $DB->get_field('question_categories', 'contextid',
                    array('id' => $question->category));
            $question->contextid = $contextid;

            // do not export hidden questions
            if (!empty($question->hidden)) {
                continue;
            }

            // do not export random questions
            if ($question->qtype == 'random') {
                continue;
            }

            // check if we need to record category change
            if ($this->cattofile) {
                if ($question->category != $trackcategory) {
                    $trackcategory = $question->category;
                    $categoryname = $this->get_category_path($trackcategory, $this->contexttofile);

                    // create 'dummy' question for category export
                    $dummyquestion = new stdClass();
                    $dummyquestion->qtype = 'category';
                    $dummyquestion->category = $categoryname;
                    $dummyquestion->name = 'Switch category to ' . $categoryname;
                    $dummyquestion->id = 0;
                    $dummyquestion->questiontextformat = '';
                    $dummyquestion->contextid = 0;
                    $expout .= $this->writequestion($dummyquestion) . "\n";
                }
            }

            // export the question displaying message
            $count++;

            if (question_has_capability_on($question, 'view', $question->category)) {
                $expout .= $this->writequestion($question, $contextid) . "\n";
            }
        }

        // continue path for following error checks
        $course = $this->course;
        $continuepath = "$CFG->wwwroot/question/export.php?courseid=$course->id";

        // did we actually process anything
        if ($count==0) {
            print_error('noquestions', 'question', $continuepath);
        }

        // final pre-process on exported data
        $expout = $this->presave_process($expout);
        return $expout;
    }
}

function custom_get_questions_category( $category, $noparent=false, $recurse=true, $export=true ) {
    global $DB;

    // Build sql bit for $noparent
    $npsql = '';
    if ($noparent) {
      $npsql = " and parent='0' ";
    }

    // Get list of categories
    if ($recurse) {
        $categorylist = question_categorylist($category->id);
    } else {
        $categorylist = array($category->id);
    }

    // Get the list of questions for the category
    list($usql, $params) = $DB->get_in_or_equal($categorylist);
	
	local_question_filters_get_filter_sql($params, $npsql, null, false, false);

	$questions = $DB->get_records_select('question', "category $usql $npsql", $params, 'qtype, name');

    // Iterate through questions, getting stuff we need
    $qresults = array();
    foreach($questions as $key => $question) {
        $question->export_process = $export;
        $qtype = question_bank::get_qtype($question->qtype, false);
        if ($export && $qtype->name() == 'missingtype') {
            // Unrecognised question type. Skip this question when exporting.
            continue;
        }
        $qtype->get_question_options($question);
        $qresults[] = $question;
    }

    return $qresults;
}


function custom_question_pluginfile($course, $context, $component, $filearea, $args, $forcedownload, array $options=array()) {
    global $DB, $CFG;

    // Special case, sending a question bank export.
    if ($filearea === 'export') {
        list($context, $course, $cm) = get_context_info_array($context->id);
        require_login($course, false, $cm);

        require_once($CFG->dirroot . '/question/editlib.php');
        $contexts = new question_edit_contexts($context);
        // check export capability
        $contexts->require_one_edit_tab_cap('export');
        $category_id = (int)array_shift($args);
        $format      = array_shift($args);
        $cattofile   = array_shift($args);
        $contexttofile = array_shift($args);
        $filename    = array_shift($args);

        // load parent class for import/export
        require_once($CFG->dirroot . '/question/format.php');
        require_once($CFG->dirroot . '/question/editlib.php');
        require_once($CFG->dirroot . '/question/format/' . $format . '/format.php');

		
		if (($classname = 'custom_qformat_' . $format) && class_exists($classname)) {
			// ok
		} else {
			$classname = 'qformat_' . $format;
			if (!class_exists($classname)) {
				send_file_not_found();
			}
		}

        $qformat = new $classname();

        if (!$category = $DB->get_record('question_categories', array('id' => $category_id))) {
            send_file_not_found();
        }

        $qformat->setCategory($category);
        $qformat->setContexts($contexts->having_one_edit_tab_cap('export'));
        $qformat->setCourse($course);

        if ($cattofile == 'withcategories') {
            $qformat->setCattofile(true);
        } else {
            $qformat->setCattofile(false);
        }

        if ($contexttofile == 'withcontexts') {
            $qformat->setContexttofile(true);
        } else {
            $qformat->setContexttofile(false);
        }

        if (!$qformat->exportpreprocess()) {
            send_file_not_found();
            print_error('exporterror', 'question', $thispageurl->out());
        }

        // export data to moodle file pool
        if (!$content = $qformat->exportprocess(true)) {
            send_file_not_found();
        }

        send_file($content, $filename, 0, 0, true, true, $qformat->mime_type());
    }

    // Normal case, a file belonging to a question.
    $qubaidorpreview = array_shift($args);

    // Two sub-cases: 1. A question being previewed outside an attempt/usage.
    if ($qubaidorpreview === 'preview') {
        $previewcontextid = (int)array_shift($args);
        $previewcomponent = array_shift($args);
        $questionid = (int) array_shift($args);
        $previewcontext = context_helper::instance_by_id($previewcontextid);

        $result = component_callback($previewcomponent, 'question_preview_pluginfile', array(
                $previewcontext, $questionid,
                $context, $component, $filearea, $args,
                $forcedownload, $options), 'newcallbackmissing');

        if ($result === 'newcallbackmissing' && $filearea = 'questiontext') {
            // Fall back to the legacy callback for backwards compatibility.
            debugging("Component {$previewcomponent} does not define the expected " .
                    "{$previewcomponent}_question_preview_pluginfile callback. Falling back to the deprecated " .
                    "{$previewcomponent}_questiontext_preview_pluginfile callback.", DEBUG_DEVELOPER);
            component_callback($previewcomponent, 'questiontext_preview_pluginfile', array(
                    $previewcontext, $questionid, $args, $forcedownload, $options));
        }

        send_file_not_found();
    }

    // 2. A question being attempted in the normal way.
    $qubaid = (int)$qubaidorpreview;
    $slot = (int)array_shift($args);

    $module = $DB->get_field('question_usages', 'component',
            array('id' => $qubaid));

    if ($module === 'core_question_preview') {
        require_once($CFG->dirroot . '/question/previewlib.php');
        return question_preview_question_pluginfile($course, $context,
                $component, $filearea, $qubaid, $slot, $args, $forcedownload, $options);

    } else {
        $dir = core_component::get_component_directory($module);
        if (!file_exists("$dir/lib.php")) {
            send_file_not_found();
        }
        include_once("$dir/lib.php");

        $filefunction = $module . '_question_pluginfile';
        if (function_exists($filefunction)) {
            $filefunction($course, $context, $component, $filearea, $qubaid, $slot,
                $args, $forcedownload, $options);
        }

        // Okay, we're here so lets check for function without 'mod_'.
        if (strpos($module, 'mod_') === 0) {
            $filefunctionold  = substr($module, 4) . '_question_pluginfile';
            if (function_exists($filefunctionold)) {
                $filefunctionold($course, $context, $component, $filearea, $qubaid, $slot,
                    $args, $forcedownload, $options);
            }
        }

        send_file_not_found();
    }
}


/**
 * This function delegates file serving to individual plugins
 *
 * @param string $relativepath
 * @param bool $forcedownload
 * @param null|string $preview the preview mode, defaults to serving the original file
 * @todo MDL-31088 file serving improments
 */
function custom_file_pluginfile($relativepath, $forcedownload, $preview = null) {
    global $DB, $CFG, $USER;
    // relative path must start with '/'
    if (!$relativepath) {
        print_error('invalidargorconf');
    } else if ($relativepath[0] != '/') {
        print_error('pathdoesnotstartslash');
    }

    // extract relative path components
    $args = explode('/', ltrim($relativepath, '/'));

    if (count($args) < 3) { // always at least context, component and filearea
        print_error('invalidarguments');
    }

    $contextid = (int)array_shift($args);
    $component = clean_param(array_shift($args), PARAM_COMPONENT);
    $filearea  = clean_param(array_shift($args), PARAM_AREA);

    list($context, $course, $cm) = get_context_info_array($contextid);

	if ($component === 'question') {
        require_once($CFG->libdir . '/questionlib.php');
        custom_question_pluginfile($course, $context, 'question', $filearea, $args, $forcedownload);
        send_file_not_found();
		exit;
	}
}

custom_file_pluginfile($relativepath, $forcedownload, $preview);

