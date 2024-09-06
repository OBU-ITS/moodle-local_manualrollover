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
 * Manual Rollover - Database upgrade
 *
 * @package    manualrollover
 * @category   local
 * @author     Joe Souch
 * @copyright  2024, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
global $CFG;
require_once($CFG->dirroot.'/group/lib.php');

function xmldb_local_manualrollover_upgrade($oldversion = 0) {
    global $DB;
    $dbman = $DB->get_manager();

    $result = true;

    if ($oldversion < 2024090602) {
        $sql = "SELECT g.*
                FROM {groups} g
                INNER JOIN {course} c ON g.courseid = c.id
                WHERE g.name LIKE '2023.%' AND g.idnumber = ''
                AND c.idnumber NOT LIKE '%ANML7004_S2_0%'
                UNION
                SELECT g.*
                FROM {groups} g
                INNER JOIN {groupings_groups} gg ON gg.groupid = g.id
                INNER JOIN {groupings} gr ON gr.id = gg.groupingid
                WHERE gr.idnumber = '' and gr.name = 'OBU System'";

        $groups = $DB->get_records_sql($sql);
        foreach($groups as $group) {
            groups_delete_group($group);
        }

        $sql = "SELECT gr.*
                FROM {groupings} gr
                WHERE gr.idnumber = '' and gr.name = 'OBU System'";

        $groupings = $DB->get_records_sql($sql);
        foreach($groupings as $grouping) {
            groups_delete_grouping($grouping);
        }

        upgrade_plugin_savepoint(true, 2024090602, 'local', 'manualrollover');
    }

    return $result;
}