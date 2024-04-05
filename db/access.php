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
 * Capability definitions for the quiz report concorsi.
 *
 * @package   quiz_concorsi
 * @copyright 2023 UPO www.uniupo.it
 * @author    Roberto Pinna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    // Is the user allowed to download quiz reviews.
    'quiz/concorsi:downloadreviews' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => [
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'mod/quiz:viewreports',
    ],

    'quiz/concorsi:archivereviews' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => [
            'editingteacher' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'mod/quiz:viewreports',
    ],
];
