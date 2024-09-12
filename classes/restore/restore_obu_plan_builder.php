<?php

namespace local_manualrollover\restore;

defined('MOODLE_INTERNAL') || die();

class restore_obu_plan_builder extends \restore_plan_builder {
    static public function build_plan($controller) {

        $plan = $controller->get_plan();

        // Add the root task, responsible for
        // preparing everything, creating the
        // needed structures (users, roles),
        // preloading information to temp table
        // and other init tasks
        $plan->add_task(new \local_manualrollover\restore\restore_obu_root_task('root_task'));
        $controller->get_progress()->progress();

        switch ($controller->get_type()) {
            case \backup::TYPE_1ACTIVITY:
                self::build_activity_plan($controller, key($controller->get_info()->activities));
                break;
            case \backup::TYPE_1SECTION:
                self::build_section_plan($controller, key($controller->get_info()->sections));
                break;
            case \backup::TYPE_1COURSE:
                self::build_course_plan($controller, $controller->get_courseid());
                break;
        }

        // Add the final task, responsible for closing
        // all the pending bits (remapings, inter-links
        // conversion...)
        // and perform other various final actions.
        $plan->add_task(new \restore_final_task('final_task'));
        $controller->get_progress()->progress();
    }
}