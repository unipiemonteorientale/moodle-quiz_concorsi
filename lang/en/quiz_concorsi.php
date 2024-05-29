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
 * Strings for component 'quiz_concorsi', language 'en', branch 'MOODLE_34_STABLE'
 *
 * @package   quiz_concorsi
 * @copyright 2023 UPO www.uniupo.it
 * @author    Roberto Pinna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Archive public exam files';
$string['concorsi'] = 'Archive public exam files';
$string['concorsireport'] = 'Archive public exam files report';
$string['quiznotclosed'] = 'Please close quiz before archive public exam files';
$string['finalize'] = 'Finalize public exam reports';
$string['areyousure'] = 'Are you sure to finalize public exam reports?i<br>Finalized report can\'t be modified.';
$string['attention'] = 'Attention please';
$string['notallgraded'] = 'Some essay answers are <strong>not graded</strong>.<br>Please grade them all before finalize.';
$string['finalizeconfirm'] = 'Finalize';
$string['refinalize'] = 'Refinalize public exam reports';
$string['zip'] = 'Compress public exam reports';
$string['downloadgradesfile'] = 'Download grades file';
$string['candidate'] = 'Candidate';
$string['attemptfiles'] = 'Attempt files ({$a})';
$string['finalizedfiles'] = 'Finalized files';
$string['questionnumber'] = 'Question {$a}';
$string['filehash'] = 'File hash';
$string['answer'] = 'Given answer';
$string['rightanswer'] = 'Right answer';
$string['gradedattempts'] = 'Graded attempts';
$string['gradebook'] = 'Gradebook';
$string['grades'] = 'Grades';
$string['attemptsarchive'] = 'Attempts archive';
$string['typepassword'] = 'Please enter zip file encryption password:';

$string['concorsi:archivereviews'] = 'Archive quiz reviews';
$string['concorsi:downloadreviews'] = 'Download quiz reviews';
// PRIVACY.
$string['privacy:metadata'] = 'The quiz Archive public exam files plugin does not store any personal data about any user.';

$string['filehash'] = 'File hash: {$a}';
$string['concorsisettings'] = 'Concorsi settings';
$string['clear'] = 'Clear';
$string['coursestartdate'] = 'Set at course start date';
$string['anonymizedates'] = 'Anonymize attempt dates';
$string['anonymizedates_desc'] = 'When enabled all quiz attempts timestamp will be cleared or set to the course start date in order to anonymize as much as possible user activity';
$string['usernamehash'] = 'Add hashed usernames in attempt report pdf';
$string['usernamehash_desc'] = 'When enabled all quiz attempt reports pdf will include the hashed username. This could be useful to prevent report repudiation';
$string['allowrefinalize'] = 'Refinalize';
$string['allowrefinalize_desc'] = 'When enabled, enabled users can finalize more than once public exams. This could limit anonynity of candidates';
$string['encryptzipfiles'] = 'Encrypt zip files';
$string['encryptzipfiles_desc'] = 'When enabled, a password will be asked to encrypt zip files';
$string['suspendmode'] = 'Suspend mode';
$string['suspendmode_desc'] = 'Candidate user will be suspended on quiz closing. You can choose if suspend all course enrolled candidates or only ones that attempt quizzes';
$string['attempted'] = 'Only candidates that attempted quiz';
$string['enrolled'] = 'All course enrolled candidates';

