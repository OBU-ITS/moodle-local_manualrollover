<?php

namespace local_manualrollover\restore;

global $CFG;

require_once($CFG->dirroot . "/local/obu_group_manager/lib.php");

use restore_groups_structure_step;

defined('MOODLE_INTERNAL') || die();

class restore_obu_groups_structure_step extends restore_groups_structure_step {
    public function process_group($data) {
        $dataobj = (object)$data;

        if (isset($dataobj->idnumber) and local_obu_group_manager_is_system_group($dataobj->idnumber)) {
            return;
        }

        parent::process_group($data);
    }

    public function process_grouping($data) {
        parent::process_grouping($data);
    }

    public function process_grouping_group($data) {
        parent::process_grouping_group($data);
    }
}