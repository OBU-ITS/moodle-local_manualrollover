<?php

namespace local_manualrollover\restore;

defined('MOODLE_INTERNAL') || die();

class restore_obu_plan extends \restore_plan {

    public function build() {
        restore_obu_plan_builder::build_plan($this->controller); // We are moodle2 always, go straight to builder
        $this->built = true;
    }
}