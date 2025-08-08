<?php
namespace local_manualrollover\restore;

defined('MOODLE_INTERNAL') || die();

class restore_obu_final_task extends \restore_final_task {

    protected function define_my_settings() {
        // no settings
    }

    protected function define_my_steps() {
        // Sections are fully restored by now; overwrite names.
        $this->add_step(new \local_manualrollover\restore\restore_obu_overwrite_section_names_step(
            'overwrite_section_names'
        ));
    }
}
