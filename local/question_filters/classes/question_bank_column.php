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

global $CFG;
require_once($CFG->dirroot . '/question/editlib.php');

echo 'asdfaf'; exit;
class local_filter_questions_question_bank_column extends \core_question\bank\column_base {
    public function get_name() {
        return 'local_searchbytags|lastmodified';
    }

    protected function get_title() {
        return 'Last modified date';
    }

    protected function display_content($question, $rowclasses) {
        if (!empty($question->timemodified)) {
            echo date('M j y G:i', $question->timemodified);
        }
    }

    /*
    public function get_extra_joins() {
        return array('tags' => 'LEFT JOIN {tag_instance} tagi ON tagi.itemid = q.id LEFT JOIN {tag} ON {tag}.id = tagi.tagid');
        return array('tags' = "JOIN (SELECT itemid, COUNT(*) as tagcount FROM mdl_tag_instance WHERE tagid IN (2, 9) GROUP BY itemid) tc ON tc.itemid=q.id AND tagcount=2
                              ");
        // MS SQL: SELECT COUNT(*) FROM (VALUES(1),(3),(5)) AS D(val);
    }
    */

    public function get_extra_joins() {
        return array();
    }

    public function get_required_fields() {
        // return array("(SELECT {tag}.name + ',' FROM {tag} WHERE id=tagi.tagid FOR XML PATH('')) AS tags");

        // for mssql
        /* */
            return array('q.timemodified');
        // For MySQL
        /*
        return array("
            (SELECT GROUP_CONCAT(name) AS tags FROM mdl_tag_instance LEFT JOIN mdl_tag ON mdl_tag.id=mdl_tag_instance.tagid WHERE itemid=q.id) as tags
        ");
        */
    }

    /**
     * Can this column be sorted on? You can return either:
     *  + false for no (the default),
     *  + a field name, if sorting this column corresponds to sorting on that datbase field.
     *  + an array of subnames to sort on as follows
     *  return array(
     *      'firstname' => array('field' => 'uc.firstname', 'title' => get_string('firstname')),
     *      'lastname' => array('field' => 'uc.lastname', 'field' => get_string('lastname')),
     *  );
     * As well as field, and field, you can also add 'revers' => 1 if you want the default sort
     * order to be DESC.
     * @return mixed as above.
     */
    public function is_sortable() {
        return 'q.timemodified';
        // return array('timemodified' => array('field' => 'q.timemodified', 'title' =>  $this->get_title()) );
    }
}

/*

    public function display_header() {
        // echo "displaying header for name: " . $this->get_name() . "title: " . $this->get_title() . ". Sortable: " . $this->is_sortable() . "<br />\n";
        echo '<th class="header ' . $this->get_classes() . '" scope="col">';
        $sortable = $this->is_sortable();
        $name = $this->get_name();
        $title = $this->get_title();
        $tip = $this->get_title_tip();
        if (is_array($sortable)) {
            if ($title) {
                echo '<div class="title">' . $title . '</div>';
            }
            $links = array();
            foreach ($sortable as $subsort => $details) {
                $links[] = $this->make_sort_link($name . '_' . $subsort,
                        $details['title'], '', !empty($details['reverse']));
            }
            echo '<div class="sorters">' . implode(' / ', $links) . '</div>';
        } else if ($sortable) {
            // echo $this->make_sort_link($name, $title, $tip);
           echo $this->make_sort_link($sortable, $title, $tip);
        } else {
            if ($tip) {
                echo '<span title="' . $tip . '">';
            }
            echo $title;
            if ($tip) {
                echo '</span>';
            }
        }
        echo "</th>\n";
    }
*/

