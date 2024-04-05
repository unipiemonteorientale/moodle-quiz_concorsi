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
 * Concorsi lib
 *
 * @package   quiz_concorsi
 * @copyright 2023 UPO www.uniupo.it
 * @author    Roberto Pinna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Serve the files from the myplugin file areas.
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file not found, just send the file otherwise and do not return anything
 */
function quiz_concorsi_pluginfile($course, $cm, $context, string $filearea, array $args,
                                   bool $forcedownload, array $options = []): bool {

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    if (!in_array($filearea, ['quiz_reviews', 'finalized'])) {
        return false;
    }

    require_login($course, true);

    if (!has_capability('quiz/concorsi:downloadreviews', $context)) {
        return false;
    }

    $itemid = array_shift($args);

    if (in_array($filearea, ['quiz_reviews', 'finalized'])) {
        if ($cm->instance !== $itemid) {
            return false;
        }
    }

    $filename = array_pop($args);
    if (empty($args)) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'quiz_concorsi', $filearea, $itemid, $filepath, $filename);
    if (empty($file)) {
        return false;
    }

    send_stored_file($file, 86200, 0, $forcedownload, $options);
}
