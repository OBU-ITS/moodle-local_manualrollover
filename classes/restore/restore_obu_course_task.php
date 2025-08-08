<?php
namespace local_manualrollover\restore;

defined('MOODLE_INTERNAL') || die();

class restore_obu_course_task extends \restore_course_task {

    protected function define_my_settings() {
        // no extra settings
    }

    protected function define_my_steps() {
        // no steps here; core will add its normal course/section/activity steps
    }

    protected function after_execute() {
        // Now sections exist; safe to overwrite names.
        $this->add_step(new \local_manualrollover\restore\restore_obu_overwrite_section_names_step(
            'overwrite_section_names'
        ));
    }
}

