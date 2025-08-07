<?php
namespace local_manualrollover\restore;

defined('MOODLE_INTERNAL') || die();

class restore_obu_overwrite_section_names_step extends \restore_execution_step {
    protected function define_execution() {
        global $DB;

        $courseid   = $this->get_courseid();
        $basepath   = $this->task->get_basepath(); // temp working dir for this restore
        $sectionsdir = $basepath . '/sections';

        if (!is_dir($sectionsdir)) {
            // Backup layout without per-section dirs: nothing to do.
            return;
        }

        $dh = @opendir($sectionsdir);
        if (!$dh) {
            return;
        }

        while (($entry = readdir($dh)) !== false) {
            if (!preg_match('/^section_(\d+)$/', $entry, $m)) {
                continue;
            }
            $xmlfile = $sectionsdir . '/' . $entry . '/section.xml';
            if (!is_readable($xmlfile)) {
                continue;
            }

            $xml = @simplexml_load_file($xmlfile);
            if ($xml === false) {
                continue;
            }

            // Old backups typically have <number> and <name>.
            $number = isset($xml->number) ? (int)$xml->number : null;
            $name   = isset($xml->name)   ? trim((string)$xml->name) : '';

            if ($number === null || $name === '') {
                continue;
            }

            // Overwrite destination section name by section number.
            $conditions = ['course' => $courseid, 'section' => $number];
            if ($DB->record_exists('course_sections', $conditions)) {
                $DB->set_field('course_sections', 'name', $name, $conditions);
            }
        }
        closedir($dh);
    }
}
