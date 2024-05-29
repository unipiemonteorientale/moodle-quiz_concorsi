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
 * Concorsi report observers
 *
 * @package   quiz_concorsi
 * @copyright 2023 UPO www.uniupo.it
 * @author    Roberto Pinna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_concorsi;

use context_module;
use html_writer;


defined('MOODLE_INTERNAL') || die();

/**
 * Observers class.
 *
 * @package   quiz_concorsi
 * @copyright 2023 UPO www.uniupo.it
 * @author    Roberto Pinna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observers {

    /**
     * Handle the quiz_attempt_submitted event.
     *
     * This save attempt in a PDF file
     *
     * @param attempt_submitted $event the event object.
     */
    public static function attempt_submitted($event) {
        global $CFG, $USER, $DB;

        $course = get_course($event->courseid);
        $attempt = $event->get_record_snapshot('quiz_attempts', $event->objectid);
        $quiz = $event->get_record_snapshot('quiz', $attempt->quiz);
        $cm = get_coursemodule_from_id('quiz', $event->get_context()->instanceid, $event->courseid);
        $eventdata = $event->get_data();

        if (!($course && $quiz && $cm && $attempt)) {
            // Something has been deleted since the event was raised. Therefore, the
            // event is no longer relevant.
            return true;
        }

        if ($attempt->preview == 0) {
            $config = get_config('quiz_concorsi');
            $attemptid = $attempt->id;

            if (!isset($config->cryptkey)) {
                $digits = range(0, 9);
                shuffle($digits);
                $config->crypkey = implode(',', $digits);
                set_config('cryptkey', $config->crypkey, 'quiz_concorsi');
            }

            if (isset($config->anonymizedates) && !empty($config->anonymizedates)) {
                if ($config->anonymizedates == 1) {
                    $attempt->timestart = 0;
                    $attempt->timefinish = 0;
                } else if ($config->anonymizedates == 2) {
                    $attempt->timestart = $quiz->timeopen;
                    $attempt->timefinish = $quiz->timeopen;
                }
                $DB->update_record('quiz_attempts', $attempt);
            }
            $context = context_module::instance($cm->id);
            $component = 'quiz_concorsi';
            $filearea = 'quiz_reviews';
            $itemid = $quiz->id;
            $idnumber = str_pad($USER->idnumber, 6, '0', STR_PAD_LEFT);
            if ($quiz->attempts == 1) {
                $filename = clean_param(fullname($USER) . '-' . $idnumber . '.pdf', PARAM_FILE);
            } else {
                $filename = clean_param(fullname($USER) . '-' . $idnumber . '-' . $attemptid . '.pdf', PARAM_FILE);
            }

            $fs = get_file_storage();
            if (!$fs->file_exists($context->id, $component, $filearea, $itemid, '/', $filename)) {
                $attemptobj = new \mod_quiz\quiz_attempt($attempt, $quiz, $cm, $course);
                $slots = $attemptobj->get_slots();
                $content = '';
                foreach ($slots as $slot) {
                    $originalslot = $attemptobj->get_original_slot($slot);
                    $number = $attemptobj->get_question_number($originalslot);

                    if ($attemptobj->is_real_question($slot)) {
                        $qa = $attemptobj->get_question_attempt($slot);
                        $content .= html_writer::tag('h2', get_string('questionnumber', 'quiz_concorsi', $number));
                        $content .= html_writer::tag('pre', str_replace(['<', '>'], ['&lt;', '&gt;'], $qa->get_question_summary()));
                        $content .= html_writer::tag('h3', get_string('answer', 'quiz_concorsi'));
                        $content .= html_writer::tag('pre', str_replace(['<', '>'], ['&lt;', '&gt;'], $qa->get_response_summary()));
                    } else {
                        $questiontext = $attemptobj->get_question_attempt($slot)->get_question($slot)->questiontext;
                        $content .= html_writer::tag('div', $questiontext);
                    }
                    $content .= html_writer::empty_tag('hr', array());
                }

                $tempdir = make_temp_directory('core_plugin/quiz_concorsi') . '/';
                $filepath = $tempdir . $filename;

                require_once($CFG->libdir . '/pdflib.php');
                $doc = new \pdf;
                $doc->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
                $userdata = ' - ' . get_string('idnumber') . ': ' . $USER->idnumber;
                if (isset($config->usernamehash) && !empty($config->usernamehash) && isset($config->cryptkey)) {
                    $userdata .= ' - '. sha1($config->cryptkey.$USER->username);
                }
                $doc->SetHeaderData(null, null, null, fullname($USER) . $userdata);
                $doc->SetFooterData(array(0, 0, 0), array(0, 0, 0));

                $doc->SetTopMargin(18);
                $doc->SetHeaderMargin(PDF_MARGIN_HEADER);
                $doc->SetFooterMargin(PDF_MARGIN_FOOTER);

                $doc->AddPage();
                $doc->writeHTML($content);
                $doc->lastPage();

                $doc->Output($filepath, 'F');

                $fileinfo = [
                    'contextid' => $context->id,
                    'component' => $component,
                    'filearea' => $filearea,
                    'itemid' => $itemid,
                    'filepath' => '/',
                    'filename' => $filename,
                ];

                $fs->create_file_from_pathname($fileinfo, $filepath);
            }
        }
        return true;
    }

}
