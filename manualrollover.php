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
 * Simplified backup/restore to provide module leader controlled manual course rollover
 *
 * @package    manualrollover
 * @category   local
 * @copyright  2015, Oxford Brookes University {@link http://www.brookes.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('../../backup/util/includes/backup_includes.php');
require_once('../../backup/util/includes/restore_includes.php');

require_once('view_manualrollover.php');

// Main page
// Start by getting the course we are called for (always starts from a course)

$params = array();
$id_first = optional_param('id', 0, PARAM_INT);
if (empty($id_first)) {
    $id_first = optional_param('id_first', 0, PARAM_INT);
}

if (!empty($id_first)) {
    $params = array('id' => $id_first);
} else {
    print_error('unspecifycourseid', 'error');
}

$course_first = $DB->get_record('course', $params, '*', MUST_EXIST);

context_helper::preload_course($course_first->id);
$context = context_course::instance($course_first->id, MUST_EXIST);

// Are we working FROM the current course TO the selected course or TO the current course FROM the selected course?
// Allow both, with parameter to set them
// FROM->TO is considered normal 'rollover'
// TO->FROM is considered 'import' (since that's how import works) and identify accordingly
// This will modify the messaging on the following screens
// Courses are referred to as 'first' and 'second', with the rollover direction depending on the type (from or to)
$rollover_type = optional_param('rtype', '', PARAM_TEXT);
if (!$rollover_type) {
    $rollover_type = "to";
}

$PAGE->set_course($course_first);
$PAGE->set_context($context);

// Check for correct permissions
require_login();
// require_capability('moodle/backup:backuptargetimport', context_system::instance());
require_capability('moodle/restore:restoretargetimport', $context);

// Which step are we on? 
// Step 0 (start) = search for module codes
// Step 1 = display list of from/to
// Step 2 = display list of course elements
// Step 3 = do the backup/restore
$step = optional_param('step', 0, PARAM_INT);

// Create view
$view = new view_manualrollover();

// Check 'first' course is in correct format
// See also same check at step 2 for 'to' course
if ($course_first->format == 'site' || $course_first->format == 'social' || $course_first->format == 'scorm') {
    $view->output_error('First course is not in weekly or topic format');
}
else {

    switch ($step) {
        case 0:        
            show_form_0($view, $rollover_type); 
        break;

        case 1:
            process_form_1($view, $rollover_type);
        break;

        case 2:
            process_form_2($view, $rollover_type);
        break;       

        case 3:
            process_form_3($view, $rollover_type);
        break;      

        default:
           show_form_0($view, $rollover_type); 
    }

}

/** 
 * Display the step 0 form for searching by module code/name
 * 
 * @global type $COURSE
 * @param view_manualrollover $view
 */

function show_form_0(view_manualrollover $view, $rtype='from') {

    global $COURSE;    
    
    $id_first = $COURSE->id;
    $name_first = $COURSE->fullname;
        
    // If there's a search term (for refining) then show it
    $course_name_second = optional_param('course_name_second', false, PARAM_TEXT);
    if ($course_name_second) {
        $search_term = $course_name_second;
    }
    else {
        // Will be searching on the module code by default, and that is the first part of the fullname
        // so split and take first part. User can always override if desired.
        $bits = explode(" ", $name_first); 

        // remove any colons (or any other punctuation?)
        $search_term = str_replace(":", "", $bits[0]);     
    }
    
    $view->output_header();
    $nextstageurl = new moodle_url('/local/manualrollover/manualrollover.php', array('step'=>0));
    if ("from" == $rtype) {
        $swapurl = new moodle_url('/local/manualrollover/manualrollover.php', array('id'=>$id_first, 'step'=>0, 'rtype'=>'to'));
    } else {
        $swapurl = new moodle_url('/local/manualrollover/manualrollover.php', array('id'=>$id_first, 'step'=>0, 'rtype'=>'from'));
    }
    
    $view->output_coursesearch_form_start($id_first, $name_first, $nextstageurl, $rtype);
    $view->output_coursesearch($search_term, $rtype);
    $view->output_coursesearch_form_end($swapurl, $rtype);
    $view->output_footer();        
               
}

/** 
 * Process and display selectable lists of to/from courses (step 1)
 * 
 * @global type $COURSE
 * @global type $DB
 * @param view_manualrollover $view
 */

function process_form_1(view_manualrollover $view, $rtype='from') {
     
    global $COURSE,
           $DB;    

    $id_first = $COURSE->id;
    $name_first = $COURSE->fullname;  
    
    // Get the module code (or other search term) for the FROM course and TO course    
    $course_name_second = optional_param('course_name_second', false, PARAM_TEXT);
    
    if (!$course_name_second) {
        $course_name_second = $_SESSION['course_name_second'];
    }
    
    if ($course_name_second) {

        // store in the session so we can get back to it
        $_SESSION['course_name_second'] = $course_name_second;
        
        // Get and display the list of courses TO
        $view->output_header();
        $nextstageurl = new moodle_url('/local/manualrollover/manualrollover.php', array('id'=>$id_first));
        $prevstageurl = new moodle_url('/local/manualrollover/manualrollover.php', array('step'=>0, 'id'=>$id_first, 'course_name_second'=>$course_name_second, 'rtype'=>$rtype));
        $view->output_courselist_form_start($nextstageurl, $id_first, $name_first, $rtype);

        $table = 'course';
        $course_name_second = strtolower($course_name_second);
        
        // Using $DB->sql_like() sounds good, but it appears to do nothing
        // sql_like_escape also appears to do little useful
        // The below therefore hard codes the strings (which is bad in theory, but at least works)
        // sql injection not a worry in a select only
        $course_name_second = str_replace("'", "''", $course_name_second); // simple replace of apostrophes - more complex cases will just fail (shouldn't legitimately happen)     
        $select = " lower(shortname) like '%" . $DB->sql_like_escape($course_name_second) . "%' OR lower(fullname) like '%" . $DB->sql_like_escape($course_name_second) . "%' order by startdate";               
        $result = $DB->get_records_select($table,$select);
             
        $max_results = 20; // arbitrary limit to number shown on a page
       
        $view->output_courselist_start($rtype);

        $num_found = count($result);
        if ($num_found < 1) {
            $view->output_courselist_none();
        }
        else {
            if ($num_found > $max_results) {
                $view->output_courselist_too_many($num_found, $max_results, $prevstageurl);     
            }
            
            $count = 0;
            foreach ($result as $thisCourse) {
                $course_array = (array) $thisCourse;   
                // don't allow option to copy into itself, and restrict to max
                if ($count <= $max_results && $id_first != $course_array['id']) {
                    $count++;
                    $tablefields = array($course_array['id'], $course_array['shortname'], $course_array['fullname']);
                    $view->output_courselist_row($rtype, $count==$num_found, $tablefields);  
                }
            }
            $view->output_courselist_end();
        }

        $view->output_courselist_form_end();
        $view->output_footer($prevstageurl);
    
    }
    
    else {
        $view->output_error("Please enter a module code or name to search for.");
    }
}

/** 
 * Process and display selectable list of course elements in given course (step 2)
 * 
 * @global type $COURSE
 * @global type $DB
 * @global type $USER
 * @param view_manualrollover $view
 */

function process_form_2(view_manualrollover $view, $rtype='from') {
    
    global $COURSE,
           $DB,
           $USER;    

    $course_id_first = $COURSE->id;
            
    // Get the IDs of the course to rollover TO (and then we will display the parts of the FROM as a form too)
    $course_id_second = optional_param('course_id_second', false, PARAM_INT);    
        
    $nextstageurl = new moodle_url('/local/manualrollover/manualrollover.php');
    $prevstageurl = new moodle_url('/local/manualrollover/manualrollover.php', array('step'=>1, 'id'=>$course_id_first, 'rtype'=>$rtype));
    
    $ok = ($course_id_first && $course_id_second);    
    
    if (!$ok) {
        $error = "Please select a module to which to copy content.";
    }

    if ($ok) {
      
        $course_second = $DB->get_record('course', array('id'=>$course_id_second), '*', MUST_EXIST);
        $context_second = context_course::instance($course_id_second);

        $course_first = $DB->get_record('course', array('id'=>$course_id_first), '*', MUST_EXIST);
        
        // Check the course_to format is also OK
        if ($course_second->format == 'site' || $course_second->format == 'social' || $course_second->format == 'scorm') {
            $ok = false;
            $error = "New course is not in weekly or topic format";  
        }
         
    }
        
    if ($ok) {        
        
        // Make sure the user can restore to that course
        // require_capability('moodle/backup:backuptargetimport', $context_to);
        require_capability('moodle/restore:restoretargetimport', $context_second);    
        
        if ($rtype == 'from') {
            $view->output_course_header($COURSE, $course_second);
        } else {
            $view->output_course_header($course_second, $COURSE);
        }
        
        // Show a warning if from course starts earlier than to course (but still permit - there might be a good reason)
        if ($rtype == 'from') {
            if ($course_second->startdate < $course_first->startdate) {
                $view->output_warning("NB that the course you are copying TO appears to have a start date earlier than the course you are copying FROM");  
            }
        } else {
            if ($course_second->startdate > $course_first->startdate) {
                $view->output_warning("NB that the course you are copying FROM appears to have a start date later than the course you are copying TO");  
            }            
        }
        // if from and to are in different formats, just give a warning in case it's a mistake
        if ($course_second->format != $course_first->format) {
            $view->output_warning("NB that the format ('weekly' or 'topic') of the FROM and TO courses differ - is this intentional?");
        }
        
        // General options (preset for rollovers to just the 1s) - copied from courserollover.php
        $options = array(
            'activities' => 1,
            'blocks' => 1,
            'filters' => 1,
            'users' => 0,
            'role_assignments' => 0,
            'comments' => 0,
            'logs' => 0);        
        
        // Get and display the list of course components - these are what we will be feeding into the rollover process (as in courserollover.php)
        // Use the backup plan (even though at this stage we won't actually do the backup) for consistency (though no doubt there are easier ways...)
        // which course we act on depends on the type of rollover (default 'from' acts on first)
        $course_to_use = $course_id_first;
        if ($rtype != 'from') {
             $course_to_use = $course_id_second;
        }
        $bc = new backup_controller(backup::TYPE_1COURSE, $course_to_use, backup::FORMAT_MOODLE, backup::INTERACTIVE_YES, backup::MODE_IMPORT, $USER->id);

        // Set general options
        foreach ($options as $name => $value) {
            $setting = $bc->get_plan()->get_setting($name);
            if ($setting->get_status() == backup_setting::NOT_LOCKED) {
                $setting->set_value($value);
            }
        }

        // Produce a listing of the elements of the backup (activities) for user to choose from
        
        $tasks = $bc->get_plan()->get_tasks();
                
        $view->output_course_element_form_start($nextstageurl, $course_id_first, $course_id_second, $rtype);
        $view->output_course_element_selects($prevstageurl); // this is a toggle (de)select all, which may not be needed on live
        $view->output_course_element_start();       
       
        foreach ($tasks as $task) {
        
            $tasksettings = $task->get_settings();
                        
            if ($tasksettings) {
            
                // Send all the tasks to the form to check (human) on inclusion/exclusion
                // but don't bother with some which can't be excluded (such as root_task)
            
                $settingtaskincluded = $tasksettings[0];
                $settingsegments = explode('_', $settingtaskincluded->get_name());
                
                if ($task->get_name() != "root_task") {
                                       
                    $settingsegments = explode('_', $settingtaskincluded->get_name());
                    
                    $checked = "checked";
                                       
                    // Pages are used for module guides - don't by default rollover these but do rollover other forms of page
                    if ($settingsegments[0] == "page") {
                        if ((strpos(strtolower($task->get_name()),'module guide') !== false)
                            || (strpos(strtolower($task->get_name()),'module description') !== false)) {
                            
                            $checked = "";
                        }                        
                    }
                    // Don't rollover forums (they will need new content)
                    // Changed 6.8.2014 - IH: I think the forums should be pre-ticked (except for news forum)
                    /*
                    if ($settingsegments[0] == "forum") {
                        $checked = "";
                    }
                     */
                    if ($settingsegments[0] == "forum") {
                        if ((strpos(strtolower($task->get_name()),'news forum') !== false)) {
                            $checked = "";
                        }
                    }                    
                    // Assignments excluded (as they should be created afresh with new dates etc)
                    if ($settingsegments[0] == "assign") {
                        $checked = "";
                    }
                    // Turn it in assignments also excluded specifically (never rollover)
                    if ($settingsegments[0] == "turnitintool") {
                        $checked = "";
                    }
                    // voicepodcaster (and anything voicepod) excluded because they appear to break the backup/restore
                    if (strpos($settingsegments[0], "voicepod") === 0) {
                        $checked = "";
                    }
                    // Include most feedbacks, but not the module evaluation (if its name includes that)
                    if ($settingsegments[0] == "feedback") {
                        if (strpos(strtolower($task->get_name()),'module evaluation') !== false) {
                            $checked = "";
                        }
                    }
                    // Don't include the 'official' module attendance activity
                    if ($settingsegments[0] == "attendance") {
                        if (strtolower($task->get_name()) == 'module attendance') {
                            $checked = "";
                        }
                    }
                   
                    // Version of line for debugging purposes but NB that this will BREAK the actual rollover
                    // $item_name = $task->get_name() . " (" . $settingsegments[0] . ")";
                    // Normal version, non-breaking
                    $item_name = $task->get_name();
                    
                    $tablefields = array($item_name, $settingsegments[0], $settingtaskincluded->get_name(), $checked);
                    
                    // Weekly/topic section headers - if included in the form and unchecked then no items in that section will be rolled over
                    // As this behaviour is counterintuitive and might not be expected, better to always include sections - if they have descriptions, they can always be rewritten
                    if ("section" == $settingsegments[0]) {
                        $view->output_course_element_section($course_first->format, $tablefields); 
                    }
                    else { 
                        $view->output_course_element_row($course_first->format, $tablefields);                   
                    }
                    
                    // $view->output_course_element_row($course_first->format, $tablefields);
                }
            }                   
        }

        $view->output_course_element_end();

        // checklist for new courses - see also course_checklist in local
        // Here all we need is a link to it as a simple reminder (not compulsory), or else exclude
        // $view->output_course_checklist($course_id_second);
        
        $view->output_course_element_form_end($nextstageurl);

        $view->output_footer($prevstageurl); 
    }
    else {
        $view->output_error($error, $prevstageurl);
    }

}

/** 
 * Process the selected list of course elements and carry out the backup/restore 
 * 
 * @global type $COURSE
 * @global type $DB
 * @global type $USER
 * @param view_manualrollover $view
 */

function process_form_3(view_manualrollover $view, $rtype='from') {
    
    global $COURSE,
           $DB,
           $USER;    
           
    // Get the ID of the course to rollover TO (depending on rollover type)
    $course_id_origin; 
    $course_id_target;
    
    if ('from' == $rtype) {
        $course_id_origin = $COURSE->id;
        $course_id_target = optional_param('course_id_second', false, PARAM_INT);   
    }
    else {
        $course_id_target = $COURSE->id;
        $course_id_origin = optional_param('course_id_second', false, PARAM_INT);           
    }
    
    $error = "";

    $ok = ($course_id_origin && $course_id_target);
    
    if (!$ok) {
        $error = "Please select course to rollover from and to.<br>";
    }
 
    if ($ok) {

        $nextstageurl = new moodle_url('/local/manualrollover/manualrollover.php', array());

        $course_origin = $DB->get_record('course', array('id'=>$course_id_origin), '*', MUST_EXIST);
        $context_origin = context_course::instance($course_id_origin);
        
        $course_target = $DB->get_record('course', array('id'=>$course_id_target), '*', MUST_EXIST);
        $context_target = context_course::instance($course_id_target);

        // Make sure the user can backup from that course
        // require_capability('moodle/backup:backuptargetimport', $context_to);
        require_capability('moodle/restore:restoretargetimport', $context_origin);
        
        // So now go through all the elements of the backup stuff
        // and check if they are included in the rollover if so, OK, if not, add to built exclusion list
        // and for simplicity, use the existing rollover stuff for the exclusion
                            
        // Set up an array of arrays of activity name, type for exclusion
        $excludeactivities = array();

        // Check all params for check box and add to exclusions if not checked
        foreach($_POST as $k => $v) {
            $pos = strpos($k, "course_element~");
            if($pos === 0) {  
                if ($v != "on") {
                
                    $bits = explode("~", $k);
                
                    if (count($bits) == 3) {
                        $thisLine = array();
                        $thisLine[0] = str_replace("_", " ", $bits[1]);
                        $thisLine[1] = $bits[2];
                        
                        // Convert exclude activities rows into associative array with activity title as key and
                        // activity type as value                        
                        $excludeactivities[$thisLine[0]] = $thisLine[1];  
                    }
                }              
            }
        } 
               
        // OK so now we have the standard list of excludes and the courses involved, 
        // we can go off and do the backup/restore
      
        $ret = backup_restore_course($course_id_origin, $course_id_target, $excludeactivities); 


        // blank the stored search term, no longer needed
        $_SESSION['course_name_second'] = null;
        
        if (!$ret[0]) {
            $view->output_error($ret[1]);
        } else {
            $new_url = new moodle_url('/course/view.php', array('id' => $course_id_target));
            $view->output_rollover_complete($course_origin, $course_target, $new_url);
        }
    }
}

/** 
 * Do the actual backup and restore, applying exclusions - this performs the 'rollover'
 *  Code largely copied from local/courserollover/courserollover.php
 * 
 * @global type $CFG
 * @global type $DB
 * @global type $USER
 * @param type $oldid
 * @param type $newid
 * @param type $excludeactivities
 * @return type
 */

function backup_restore_course($oldid, $newid, $excludeactivities) {
    global $CFG,
           $DB,
           $USER,
		   $SESSION;

    // Check for hyperactive fingers
	if (($SESSION->local_manualrollover_oldid == $oldid) && ($SESSION->local_manualrollover_newid == $newid) && (time() - $SESSION->local_manualrollover_time < 60)) {
        return array(false, 'Rollover was completed');
    }

	// General options
    $options = array(
        'activities' => 1,
        'blocks' => 1,
        'filters' => 1,
        'users' => 0,
        'role_assignments' => 0,
        'comments' => 0,
        'logs' => 0);

    // Check old / new courses exist (and freshly fetch them from the DB - not really necessary but no harm)
    if (!$oldcourse = $DB->get_record('course', array('id'=>$oldid), '*')) {
        return array(false, 'Old course not found');
    }
    if (!$newcourse = $DB->get_record('course', array('id'=>$newid), '*')) {
        return array(false, 'New course not found');
    }

    // Check old course is in correct format
    if ($oldcourse->format == 'site' || $oldcourse->format == 'social' || $oldcourse->format == 'scorm') {
        return array(false, 'Old course is not in weekly or topic format');
    }

    // Perform backup
    $bc = new backup_controller(backup::TYPE_1COURSE, $oldid, backup::FORMAT_MOODLE, backup::INTERACTIVE_YES, backup::MODE_IMPORT, $USER->id);

    // Set general options
    foreach ($options as $name => $value) {
        $setting = $bc->get_plan()->get_setting($name);
        if ($setting->get_status() == backup_setting::NOT_LOCKED) {
            $setting->set_value($value);
        }
    }

    // Exclude specified activities from backup
    $tasks = $bc->get_plan()->get_tasks();
    foreach ($tasks as $task) {

        if ( isset($excludeactivities[ $task->get_name() ]) ) {
        
            $tasksettings = $task->get_settings();
            $settingtaskincluded = $tasksettings[0];
            $settingsegments = explode('_', $settingtaskincluded->get_name());
                        
            if ($settingsegments[0] == $excludeactivities[ $task->get_name() ]) {
                        
                if ($settingtaskincluded->get_status() == backup_setting::NOT_LOCKED) {
                    $settingtaskincluded->set_value(0);
                }
            }
        }        
    }

    $backupid = $bc->get_backupid();
    $backupbasepath = $bc->get_plan()->get_basepath();

    $bc->save_controller();
    $bc->finish_ui();

    $bc->execute_plan();
    $bc->destroy();

    // Check backup succeeded
    $tempdestination = $CFG->tempdir . '/backup/' . $backupid;
    if (!file_exists($tempdestination) || !is_dir($tempdestination)) {
        return array(false, 'Error backing up old course');
    }

    // Perform restoration
    $rc = new restore_controller($backupid, $newid, backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id, backup::TARGET_CURRENT_ADDING);

    // Set general options
    foreach ($options as $name => $value) {
        $setting = $rc->get_plan()->get_setting($name);
        if ($setting->get_status() == backup_setting::NOT_LOCKED) {
            $setting->set_value($value);
        }
    }

    // Check for errors in backup
    if (!$rc->execute_precheck()) {
        $precheckresults = $rc->get_precheck_results();
        if (is_array($precheckresults) && !empty($precheckresults['errors'])) {
            if (empty($CFG->keeptempdirectoriesonbackup)) {
                fulldelete($backupbasepath);
            }

            $errorinfo = '';

            foreach ($precheckresults['errors'] as $error) {
                $errorinfo .= $error;
            }

            if (array_key_exists('warnings', $precheckresults)) {
                foreach ($precheckresults['warnings'] as $warning) {
                    $errorinfo .= $warning;
                }
            }

            return array(false, $errorinfo);
        }
    }

    $rc->execute_plan();

    $rc->destroy();
    fulldelete($tempdestination);

	// Save details to mitigate against repeated clicks
	$SESSION->local_manualrollover_oldid = $oldid;
	$SESSION->local_manualrollover_newid = $newid;
	$SESSION->local_manualrollover_time = time();

    return array(true, '');
}






