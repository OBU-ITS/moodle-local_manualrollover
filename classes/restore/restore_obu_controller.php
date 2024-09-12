<?php

namespace local_manualrollover\restore;

defined('MOODLE_INTERNAL') || die();

class restore_obu_controller extends \restore_controller {
    protected function load_plan() {
        // First of all, we need to introspect the moodle_backup.xml file
        // in order to detect all the required stuff. So, create the
        // monster $info structure where everything will be defined
        $this->log('loading backup info', \backup::LOG_DEBUG);
        $this->info = \backup_general_helper::get_backup_information($this->tempdir);

        // Set the controller type to the one found in the information
        $this->type = $this->info->type;

        // Set the controller samesite flag as needed
        $this->samesite = \backup_general_helper::backup_is_samesite($this->info);

        // Now we load the plan that will be configured following the
        // information provided by the $info
        $this->log('loading controller plan', \backup::LOG_DEBUG);
        $this->plan = new \local_manualrollover\restore\restore_obu_plan($this);
        $this->plan->build(); // Build plan for this controller
        $this->set_status(\backup::STATUS_PLANNED);
    }
}
