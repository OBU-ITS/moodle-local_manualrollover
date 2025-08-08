<?php

namespace local_manualrollover\restore;

defined('MOODLE_INTERNAL') || die();

class restore_obu_root_task extends \restore_root_task {
    public function build() {
        // Conditionally create the temp table (can exist from prechecks) and delete old stuff
        $this->add_step(new \restore_create_and_clean_temp_stuff('create_and_clean_temp_stuff'));

        // Now make sure the user that is running the restore can actually access the course
        // before executing any other step (potentially performing permission checks)
        $this->add_step(new \restore_fix_restorer_access_step('fix_restorer_access'));

        // If we haven't preloaded information, load all the included inforef records to temp_ids table
        $this->add_step(new \restore_load_included_inforef_records('load_inforef_records'));

        // Load all the needed files to temp_ids table
        $this->add_step(new \restore_load_included_files('load_file_records', 'files.xml'));

        // If we haven't preloaded information, load all the needed roles to temp_ids_table
        $this->add_step(new \restore_load_and_map_roles('load_and_map_roles'));

        // If we haven't preloaded information and are restoring user info, load all the needed users to temp_ids table
        $this->add_step(new \restore_load_included_users('load_user_records'));

        // If we haven't preloaded information and are restoring user info, process all those needed users
        // marking for create/map them as needed. Any problem here will cause exception as far as prechecks have
        // performed the same process so, it's not possible to have errors here
        $this->add_step(new \restore_process_included_users('process_user_records'));

        // Unconditionally, create all the needed users calculated in the previous step
        $this->add_step(new \restore_create_included_users('create_users'));

        // Then force name overwrite with ours
        $this->add_step(new \local_manualrollover\restore\restore_obu_overwrite_section_names_step(
            'overwrite_section_names'
        ));

        // Unconditionally, load create all the needed groups and groupings
        $this->add_step(new \local_manualrollover\restore\restore_obu_groups_structure_step('create_obu_groups_and_groupings', 'groups.xml'));

        // Unconditionally, load create all the needed scales
        $this->add_step(new \restore_scales_structure_step('create_scales', 'scales.xml'));

        // Unconditionally, load create all the needed outcomes
        $this->add_step(new \restore_outcomes_structure_step('create_scales', 'outcomes.xml'));

        // If we haven't preloaded information, load all the needed categories and questions (reduced) to temp_ids_table
        $this->add_step(new \restore_load_categories_and_questions('load_categories_and_questions'));

        // If we haven't preloaded information, process all the loaded categories and questions
        // marking them for creation/mapping as needed. Any problem here will cause exception
        // because this same process has been executed and reported by restore prechecks, so
        // it is not possible to have errors here.
        $this->add_step(new \restore_process_categories_and_questions('process_categories_and_questions'));

        // Unconditionally, create and map all the categories and questions
        $this->add_step(new \restore_create_categories_and_questions('create_categories_and_questions', 'questions.xml'));

        // Do NOT add a sections.xml step anymore — core restores sections from
        // course/sections/section_*/section.xml in 4.5.

        // After all core tasks are done, overwrite section names.
        $final = $this->plan->get_final_task();
        $final->add_step(new \local_manualrollover\restore\restore_obu_overwrite_section_names_step(
            'overwrite_section_names'
        ));

        // At the end, mark it as built
        $this->built = true;        // At the end, mark it as built
    }
}