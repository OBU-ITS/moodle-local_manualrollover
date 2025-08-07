<?php

namespace local_manualrollover\restore;

use restore_structure_step;
use restore_path_element;
use context_course;
use moodle_exception;
use stdClass;

class restore_obu_overwrite_section_names_step extends restore_structure_step {

    protected function define_structure() {
        $paths = [];
        $paths[] = new restore_path_element('section', '/section');
        return $paths;
    }

    protected function process_section($data) {
        global $DB;

        $data = (object)$data;
        $courseid = $this->get_courseid();
        $sectionnum = $data->number;

        // Do not proceed unless name is set.
        if (!isset($data->name)) {
            return;
        }

        // Update section name in the destination course.
        $conditions = ['course' => $courseid, 'section' => $sectionnum];
        if ($DB->record_exists('course_sections', $conditions)) {
            $DB->set_field('course_sections', 'name', $data->name, $conditions);
        }
    }

    protected function after_execute() {
        // Add related files for section summaries if needed.
        $this->add_related_files('course', 'section', 'course_sections');
    }
}
