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
 * Post-install script for the quiz concorsi report.
 *
 * @package   quiz_concorsi
 * @copyright 2023 UPO www.uniupo.it
 * @author    Roberto Pinna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Quiz concorsi report upgrade code.
 *
 * @param int $oldversion The plugin old version number.
 */
function xmldb_quiz_concorsi_upgrade($oldversion) {
    global $DB;

    if ($oldversion < 2023102400) {
        $query = ['component' => 'quiz_concorsi', 'filearea' => 'finalized', 'filepath' => '/'];
        $filerecords = $DB->get_records('files', $query);
        if (!empty($filerecords)) {
            $fs = get_file_storage();
            foreach ($filerecords as $filerecord) {
                $newfilepath = '/';
                $extension = mb_substr($filerecord->filename, mb_strrpos($filerecord->filename, '.'));
                switch ($extension) {
                    case '.zip':
                        $newfilepath = '/attemptsarchive/';
                    break;
                    case '.pdf':
                        $newfilepath = '/gradedattempts/';
                    break;
                    case '.xlsx':
                        $newfilepath = '/gradebook/';
                    break;
                }

                if ($filerecord->filename != '.') {
                    $file = $fs->get_file(
                        $filerecord->contextid,
                        $filerecord->component,
                        $filerecord->filearea,
                        $filerecord->itemid,
                        $filerecord->filepath,
                        $filerecord->filename
                    );
                    $file->rename($newfilepath, $filerecord->filename);
                }
            }
        }
    }

    if ($oldversion < 2024052900) {
        $themeconfigs = $DB->get_records('config_plugins', ['plugin' => 'theme_concorsi']);

        $confignames = ['anonymizedates', 'usernamehash', 'allowrefinalize', 'encryptzipfiles', 'suspendmode', 'cryptkey'];
        foreach ($themeconfigs as $config) {
            if (in_array($config->name, $confignames)) {
                $config->plugin = 'quiz_concorsi';
                $DB->update_record('config_plugins', $config);
            }
        }
    }

    return true;
}
