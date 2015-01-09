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
 * Form elements for the manual rollover process
 *
 * @package    manualrollover
 * @category   local
 * @copyright  2015, Oxford Brookes University {@link http://www.brookes.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once("{$CFG->libdir}/formslib.php");


class view_manualrollover {

    /** this */
    private $form = null;

    /** constructor */
    function __construct() {
        $this->setup_page();
    }

    /** set form */
    function set_form(moodleform $form) {
        $this->form = $form;
    }

    /** Set up the page and headings
     * 
     * @global type $PAGE
     */
    
    private function setup_page() {
        global $PAGE;
        $PAGE->set_url('/local/manualrollover/manualrollover.php');
        $PAGE->set_pagelayout('standard');
        $PAGE->set_title(get_string('manualrollover', 'local_manualrollover'));
        $PAGE->set_heading(get_string('manualrollover', 'local_manualrollover'));
    }

    /** Output text header
     * 
     * @global type $OUTPUT
     */
    
    private function op_header() {
        global $OUTPUT;
        echo $OUTPUT->header();    
        echo $OUTPUT->heading(get_string('manualrollover', 'local_manualrollover'));
    }

    /** Output the footer
     * 
     * @global type $OUTPUT
     */

    private function op_footer() {
        global $OUTPUT;
        echo $OUTPUT->footer();
    }

    /** Output the header
     * 
     */
    
    function output_header() {
        $this->setup_page();
        $this->op_header();
    }

    /** Ouput footer text (back to previous link)
     * 
     * @global type $CFG
     * @param type $prev_url
     */
    
    function output_footer($prev_url = '') {
        global $CFG;
        if ($prev_url) {
            // echo '<p><br><a href="' . $CFG->wwwroot . '/local/manualrollover/manualrollover.php">Back to course roll-over form</a></p>';
            echo '<p><br><a href="' . $prev_url . '">' . get_string('previous_step', 'local_manualrollover') . '</a></p>';
        }
        $this->op_footer();
    }

    /** Output text error
     * 
     * @global type $CFG
     * @param type $error
     * @param type $prev_url
     */
    
    function output_error($error='', $prev_url = '') {
        global $CFG;
        $this->setup_page();
        $this->op_header();
        echo '<p>' . $error . '</p>';
        // echo '<p><br><a href="' . $CFG->wwwroot . '/local/manualrollover/manualrollover.php">Back to course roll-over form</a></p>';
        $this->output_footer($prev_url);
    }


    /** These output the page elements for stage 1 (searching for courses to rollover to)
     * 
     * @param type $first_id
     * @param type $first_name
     * @param type $nextstageurl
     */

    function output_coursesearch_form_start($first_id, $first_name, $nextstageurl, $rtype='from') {

        echo html_writer::start_tag('div', array('class'=>'import-course-selector backup-restore'));
        echo html_writer::start_tag('form', array('method'=>'get', 'action'=>$nextstageurl->out_omit_querystring()));
        echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'step', 'value'=>'1'));
        echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'id_first', 'value'=>$first_id));
        echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'rtype', 'value'=>$rtype));

        echo get_string('content_'.$rtype, 'local_manualrollover') . $first_name;
        echo "<br>"; 
        echo "<br>";  

    }

    /** Output form elements for the course search box
     * 
     * @param type $default
     */
    
    function output_coursesearch($default = '', $rtype='from') {
        
        $to_from = "from";
        if ("from" == $rtype) {
            $to_from = "to";
        }
        
        echo '<table class="generaltable boxaligncenter">';
        echo '<tr class="heading">';
        echo '<th colspan="2">' . get_string("content_".$to_from."_search", 'local_manualrollover') . '</th>';
        echo '</tr>';
        
        echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'rtype', 'value'=>$rtype));
        
        echo '<tr>';
        echo '<td>';
        echo '<p>' . get_string('enter_code', 'local_manualrollover');
        echo '</td>';

        echo '<td>';
        echo html_writer::empty_tag('input', array('type'=>'text', 'name'=>'course_name_second', 'value'=>$default));
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td colspan="2">';
        echo html_writer::empty_tag('input', array('type'=>'submit', 'value'=>'Search'));
        echo '</td>';
        echo '</tr>';

        echo '</table>';

    }
    
    /** Close the course search
     * 
     */
    
    function output_coursesearch_form_end($swapurl, $rtype) {
       
        if ("from" == $rtype) {           
            echo '<p><br><a href="' . $swapurl . '">' . get_string('swapto', 'local_manualrollover') . '</a></p>';
        }
        else {
            echo '<p><br><a href="' . $swapurl . '">' . get_string('swapfrom', 'local_manualrollover') . '</a></p>';
        }
        
        echo html_writer::end_tag('form');
        echo html_writer::end_tag('div');
    }        
    

    /** Output the page elements for stage 2 (choosing the courses to rollover to/from)
     * 
     * @param type $nextstageurl
     * @param type $id_first
     * @param type $name_first
     */

    function output_courselist_form_start($nextstageurl, $id_first, $name_first, $rtype='from') {
                
        echo html_writer::start_tag('div', array('class'=>'import-course-selector backup-restore'));
        echo html_writer::start_tag('form', array('method'=>'get', 'action'=>$nextstageurl->out_omit_querystring()));
        echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'step', 'value'=>'2'));
        echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'id_first', 'value'=>$id_first));
        echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'rtype', 'value'=>$rtype));
    
        echo get_string('content_'.$rtype, 'local_manualrollover') . $name_first;
        echo "<br>";    
        echo "<br>";      
    
    }
    
     
    /** Open the courselist
     * 
     * @param type $type
     */
    
    function output_courselist_start($rtype) {
        
        $to_from = "from";
        if ("from" == $rtype) {
            $to_from = "to";
        }
        
        echo '<table class="generaltable boxaligncenter">';
        echo '<tr class="heading">';
        echo '<th colspan="3">';
        echo get_string('content_'.$to_from.'_select', 'local_manualrollover');
        echo '</th>';
        echo '</tr>';
    }

    /** Close the courselist
     * 
     */
    
    function output_courselist_form_end() {
       
        echo html_writer::end_tag('form');
        echo html_writer::end_tag('div');
    }      
    
    /** Ouput an individual course row in the course search results list
     * 
     * @param type $type
     * @param type $checked
     * @param array $fields
     */
    
    function output_courselist_row($type, $checked, array $fields) {
       
        $is_checked = $checked ? 'true' : '';
        
        echo '<tr>';
        
        echo '<td>';
        echo html_writer::empty_tag('input', array('type'=>'radio', 'name'=>'course_id_second', 'value'=>$fields[0], 'checked'=>$is_checked));
        echo '</td>';
        
        echo '<td>' . $fields[1] . '</td>';
        echo '<td>' . $fields[2] . '</td>';
          
        echo '</tr>';
    }

    /** Text for no search results
     * 
     */
    
    function output_courselist_none() {
        echo '<tr>';
        
        echo '<td>';
        echo get_string('no_results', 'local_manualrollover');
        echo '</td>';
                  
        echo '</tr>';
        
        echo '</table>';
    }    
    
    /** Warning text for too many results found
     * 
     * @param type $num
     * @param type $max
     * @param type $refine_url
     */
    
    function output_courselist_too_many($num, $max, $refine_url) {

        echo "<br><strong>Found $num results - showing the first $max. Try <a href='$refine_url'>refining your search</a> to show fewer results.</strong><br>";

    } 
    
    /** Submit button and close search results list
     * 
     */

    function output_courselist_end() {
       
        echo '<td colspan="3">';
        echo html_writer::empty_tag('input', array('type'=>'submit', 'value'=>get_string('continue')));
        echo '</td>';
        
        echo '</table>';
    }

    /** Generic warning
     * 
     * @param type $text
     */
    
    function output_warning($text) {
        
        echo "<strong>$text</strong>";
        echo "<br>";
        echo "<br>";
        
    }      
     
    /** Output the header for course display
     * 
     * @param type $course_from
     * @param type $course_to
     */
    
    function output_course_header($course_from, $course_to) {
      
        $this->setup_page();
        $this->op_header();
               
        echo get_string('content_from', 'local_manualrollover') . $course_from->fullname;
        echo "<br>";
        echo get_string('content_to', 'local_manualrollover') . $course_to->fullname;
        echo "<br><br>";
        
        echo "Please indicate (by ticking the boxes) all elements from " . $course_from->fullname . " that you wish to copy over to course " .  $course_to->fullname ;
        echo "<br><br>";
        
    }
    
    /** Output the page elements for stage 3 (choosing elements to exclude from the rollover)
     * 
     * @param type $nextstageurl
     * @param type $course_id_first
     * @param type $course_id_second
     */

    function output_course_element_form_start($nextstageurl, $course_id_first, $course_id_second, $rtype='from') {
        
        echo html_writer::start_tag('div', array('class'=>'import-course-selector backup-restore'));
        echo html_writer::start_tag('form', array('method'=>'post', 'action'=>$nextstageurl->out_omit_querystring()));

        echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'step', 'value'=>'3'));
        echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'id_first', 'value'=>$course_id_first));
        echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'course_id_second', 'value'=>$course_id_second));
        echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'rtype', 'value'=>$rtype));
    }
    
    /** Close the form
     * 
     */
    
    function output_course_element_form_end() {
       
        echo html_writer::end_tag('form');
        echo html_writer::end_tag('div');
    }    

    /** Open the course element list
     * 
     */
    
    function output_course_element_start() {
        
        echo '<table class="generaltable boxaligncenter">';
        echo '<tr class="heading">';
        echo '<th>Course element</th>';
        echo '<th>Tick to include<br>in rollover</th>';
        echo '</tr>';
    }

    /** Output an individual course element list
     * 
     * @global type $OUTPUT
     * @param type $type
     * @param type $fields
     */
    
    function output_course_element_row($type, $fields) {

        global $OUTPUT;
        echo '<tr>';
        
        echo '<td>'; 
        
        if ("section" == $fields[1]) {
            if ("weeks" == $type) {
                echo "Week " . $fields[0];
            } else {
                echo "Topic " . $fields[0];
            }
        } else {
            $activityicon = $OUTPUT->pix_icon('icon', $fields[1], $fields[1], array('class'=>'icon'));
            echo $activityicon;
            echo $fields[0];
        }

        echo '</td>';        
        echo '<td>'; 
        
        $id = $fields[0] . "~" . $fields[1];

        // Ack hideous - this is what Moodle's advcheckbox does (but that requires use of Moodle form (which could be, but isn't, used))
        echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'course_element~'.$id, 'value'=>'0'));
        if ($fields[3] == "checked") {
            echo html_writer::empty_tag('input', array('type'=>'checkbox', 'name'=>'course_element~'.$id, 'checked'=>"checked"));        
        } else {
            echo html_writer::empty_tag('input', array('type'=>'checkbox', 'name'=>'course_element~'.$id));
        }
        echo '</td>'; 
         
        echo '</tr>';
  
    }
    
    /** Output a section header (might not be used)
     * 
     * @param type $fields
     */
    
    function output_course_element_section($course_type, $fields) {

        echo '<tr>';
        echo '<td colspan="2"><strong>'; 
        if ("weeks" == $course_type) {
            echo "Week ";
        } else {
            echo "Topic ";
        }
        echo $fields[0];
        echo '</strong></td>';
        echo '</tr>';
    }    
        
    /** Buttons to select/deselect all course elements (if that is a good idea) - don't submit the form, just JS it
     * Could use moodle_form->add_checkbox_controller() but this isn't a moodle_form, as above
     * 
     * @param type $prev_stage_url
     */
    
    function output_course_element_selects($prev_stage_url) {
 
        echo "\n<script language=\"JavaScript\">\n";     
        echo "    function toggle(source) {\n";
        echo "        checkboxes = document.getElementsByTagName('input');\n";
        echo "        for(var i=0, n=checkboxes.length;i<n;i++) {\n";
        echo "            if(checkboxes[i].name != null && checkboxes[i].name.lastIndexOf('course_element~', 0) === 0) {\n";
        echo "                checkboxes[i].checked = source.checked;\n";
        echo "            }\n";
        echo "        }\n";
        echo "    }\n";  
        echo "</script>\n";
    
        echo "<input type=\"checkbox\" onClick=\"toggle(this)\" /> Select/deselect all elements<br/> (but please note that elements you would not usually want to copy over are deselected by default - if you change your mind you can <a href='$prev_stage_url'>go back to previous step</a> and start again)\n";
        
    }
    
    /** Course element list close and submit button
     * 
     */    
    
    function output_course_element_end() {
        
        echo '<tr>';
        echo '<td colspan="3">';
        echo html_writer::empty_tag('input', array('type'=>'submit', 'value'=>get_string('perform_button', 'local_manualrollover')));
        echo get_string('perform_notes', 'local_manualrollover');
        echo '</td>';
        echo '</tr>';
       
        echo '</table>';
    }

    /** Optional course checklist link
     * 
     */
    
    function output_course_checklist($courseid) {
              
        echo html_writer::tag("span", get_string('checklist_start', 'local_manualrollover'));
        echo html_writer::link("/local/coursechecklist/checklist_form.php?courseids=".$courseid, get_string('checklist_link_text', 'local_manualrollover'), array("style"=>"color:red"));
        echo html_writer::tag("span", get_string('checklist_finish', 'local_manualrollover'));        
        
    }
    
    /** Output the final - success, we hope - page 
     *
     */

    function output_rollover_complete($course_from, $course_to, $new_url) {
        $this->setup_page();
        $this->op_header();

        echo 'Rollover successfully completed from ' . $course_from->fullname;
        echo '<br>to <a href="' . $new_url . '">' . $course_to->fullname . '</a>.';

        $this->op_footer();
    }
}
