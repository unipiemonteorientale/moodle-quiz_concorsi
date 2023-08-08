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
 * This file defines the quiz concorsi report class.
 *
 * @package   quiz_concorsi
 * @copyright 2023 UPO www.uniupo.it
 * @author    Roberto Pinna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_quiz\local\reports\report_base;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/report/reportlib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->libdir . '/pagelib.php');
require_once($CFG->libdir . '/filestorage/zip_packer.php');

define('ENROLLED', 1);
define('ATTEMPTED', 2);

/**
 * Quiz report subclass for the concorsi report.
 *
 * This report lists some combination of
 *  * what question each student saw (this makes sense if random questions were used).
 *  * the response they gave.
 *
 * @package   quiz_concorsi
 * @copyright 2023 UPO www.uniupo.it
 * @author    Roberto Pinna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_concorsi_report extends mod_quiz\local\reports\report_base {
    /** @var object the questions that comprise this quiz.. */
    protected $questions;
    /** @var object course module object. */
    protected $cm;
    /** @var object the quiz settings object. */
    protected $quiz;
    /** @var context the quiz context. */
    protected $context;
    /** @var course the course. */
    protected $course;
    /** @var component this component. */
    protected $component;
    /** @var reviewarea review files area. */
    protected $reviewarea;
    /** @var finalizedarea finalized files area. */
    protected $finalizedarea;

    /**
     * Display the report.
     *
     * @param object $quiz this quiz.
     * @param object $cm the course-module for this quiz.
     * @param object $course the course we are in.
     * @return bool
     * @throws moodle_exception
     */
    public function display($quiz, $cm, $course) {
        global $OUTPUT;

        $this->quiz = $quiz;
        $this->cm = $cm;
        $this->course = $course;

        $this->component = 'quiz_concorsi';
        $this->reviewarea = 'quiz_reviews';
        $this->finalizedarea = 'finalized';

        $canrefinalize = get_config('theme_concorsi', 'allowrefinalize');

        // Check permissions.
        $this->context = context_module::instance($cm->id);
        require_capability('mod/quiz:grade', $this->context);

        $action = optional_param('action', '', PARAM_ALPHA);
        if (!empty($action)) {
            if ($action == 'downloadgrades') {
                $this->download_grades_file();
                exit();
            }
        }

        // Start output.
        $this->print_header_and_tabs($cm, $course, $quiz, 'concorsi');

        echo $OUTPUT->single_button(
            new moodle_url('/mod/quiz/report.php', array(
                    'id' => $cm->id,
                    'mode' => 'concorsi',
                    'action' => 'downloadgrades')
            ),
            get_string('downloadgradesfile', 'quiz_concorsi'),
            'post'
        );

        if (!empty($quiz->timeclose) && ($quiz->timeclose < time())) {
            $itemid = $this->quiz->id;

            $fs = get_file_storage();
            $files = $fs->get_area_files($this->context->id, $this->component, $this->reviewarea, $itemid);

            if (!empty($files)) {
                $this->print_files($files, 'attemptfiles', 'quiz-attempts-files');

                $finalizedfiles = $fs->get_area_files($this->context->id, $this->component, $this->finalizedarea, $itemid);
                $zipped = false;
                $finalized = false;
                if (!empty($finalizedfiles)) {
                    foreach ($finalizedfiles as $finalizedfile) {
                        $finalizedfilename = $finalizedfile->get_filename();
                        if ($finalizedfilename !== '.') {
                            if (!$zipped) {
                                $zipped = $finalizedfilename == $this->get_finalized_filename('.zip');
                            }
                            if (!$finalized) {
                                $finalized = $finalizedfilename == $this->get_finalized_filename('.pdf');
                            }
                        }
                    }
                }

                if (!empty($action)) {
                    if (has_capability('quiz/concorsi:archivereviews', $this->context)) {
                        if ($zipped && (!$finalized || $canrefinalize) && ($action == 'finalize')) {
                            $finalized = $this->finalize_quiz();
                        }
                        if (!$zipped && ($action == 'zip')) {
                            $zipped = $this->zip_reviews($files);
                        }
                        if (($action == 'finalize') || ($action == 'zip')) {
                            $suspened = $this->suspend_quiz_users();
                        }
                        $finalizedfiles = $fs->get_area_files($this->context->id, $this->component, $this->finalizedarea, $itemid);
                    }
                }

                if (!empty($finalizedfiles)) {
                    $this->print_files($finalizedfiles, 'finalizedfiles', 'quiz-finalized-files');
                }

                if (has_capability('quiz/concorsi:archivereviews', $this->context)) {
                    if (!$zipped) {
                        echo $OUTPUT->single_button(
                            new moodle_url('/mod/quiz/report.php', array(
                                    'id' => $cm->id,
                                    'mode' => 'concorsi',
                                    'action' => 'zip')
                            ),
                            get_string('zip', 'quiz_concorsi'),
                            'post'
                        );
                    }
                    if ($zipped && (!$finalized || $canrefinalize)) {
                        $finalizestr = get_string('finalize', 'quiz_concorsi');
                        $confirmattrs = array();
                        if ($finalized && $canrefinalize) {
                            $finalizestr = get_string('refinalize', 'quiz_concorsi');
                        }
                        if (!$finalized && !$canrefinalize) {
                            $destination = 'javascript:document.getElementsByName("finalize")[0].parentElement.submit();';
                            $confirmattrs = array(
                                'name' => 'finalize',
                                'data-modal' => 'confirmation',
                                'data-modal-title-str' => json_encode(['finalize', 'quiz_concorsi']),
                                'data-modal-content-str' => json_encode(['areyousure', 'quiz_concorsi']),
                                'data-modal-yes-button-str' => json_encode(['finalizeconfirm', 'quiz_concorsi']),
                                'data-modal-destination' => $destination,
                            );
                        }
                        echo $OUTPUT->single_button(
                            new moodle_url('/mod/quiz/report.php', array(
                                    'id' => $cm->id,
                                    'mode' => 'concorsi',
                                    'action' => 'finalize')
                            ),
                            $finalizestr,
                            'post',
                            $confirmattrs
                        );
                    }
                }
            }
        } else {
            echo html_writer::tag('h3', new lang_string('quiznotclosed', 'quiz_concorsi'));
        }

        return true;
    }

    /**
     * Get the URL of the front page of the report that lists all the questions.
     * @return string the URL.
     */
    protected function base_url() {
        return new moodle_url('/mod/quiz/report.php',
            array('id' => $this->cm->id, 'mode' => 'concorsi'));
    }

    /**
     * Print list of file links.
     *
     * @param array $files Files to print.
     * @param string $langstr Lang string identifier for list header.
     * @param string $class CSS class name for the list.
     *
     * @return void
     */
    private function print_files($files, $langstr, $class) {
        $filelist = '';
        $count = 0;

        foreach ($files as $file) {
            $filename = $file->get_filename();
            if ($filename != '.') {
                if (has_capability('quiz/concorsi:downloadreviews', $this->context)) {
                    $urldownload = moodle_url::make_pluginfile_url(
                        $file->get_contextid(),
                        $file->get_component(),
                        $file->get_filearea(),
                        $file->get_itemid(),
                        $file->get_filepath(),
                        $filename,
                        true
                    );
                    $downloadlink = html_writer::tag('a', $filename, array('href' => $urldownload));
                    $filelist .= html_writer::tag('li', $downloadlink);
                } else {
                    $filelist .= html_writer::tag('li', $filename);
                }
                $count++;
            }
        }
        if ($count > 0) {
            $content = html_writer::tag('h3', get_string($langstr, 'quiz_concorsi', $count), array('class' => $class . '-title'));
            $content .= html_writer::start_tag('ul', array('class' => $class . '-list'));
            $content .= $filelist;
            $content .= html_writer::end_tag('ul');
            echo html_writer::tag('div', $content, array('class' => $class));
        }
    }

    /**
     * Print list of file links.
     *
     * @param string $extension Filename extension.
     *
     * @return void
     */
    private function get_finalized_filename($extension) {
        $filenameparts = array();

        $filenameparts[] = $this->course->shortname;
        $filenameparts[] = userdate($this->course->startdate, '%d-%m-%Y');
        $filenameparts[] = $this->quiz->name;
        $filenameparts[] = $this->quiz->id;

        return clean_filename(implode('-', $filenameparts) . $extension);
    }

    /**
     * Create and store a zip with files.
     *
     * @param array $files Files to print.
     *
     * @return boolean True on success and false on failure.
     */
    private function zip_reviews($files) {
        if (!empty($files)) {
            $zipfiles = array();
            foreach ($files as $file) {
                $filename = $file->get_filename();
                if ($filename != '.' && !$file->is_directory()) {
                    $zipfiles[$filename] = $file;
                }
            }
            if (!empty($zipfiles)) {
                $zip = new zip_packer();
                $zipfilename = $this->get_finalized_filename('.zip');
                if ($zip->archive_to_storage($zipfiles, $this->context->id, $this->component, $this->finalizedarea,
                                         $this->quiz->id, '/', $zipfilename) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Suspend users that tried the quiz.
     *
     * @return boolean True on success and false on failure.
     */
    private function suspend_quiz_users() {
        global $DB;

        $suspendmode = get_config('theme_concorsi', 'suspendmode');

        $result = true;

        switch ($suspendmode) {
            case ATTEMPTED:
                $attempts = $DB->get_records('quiz_attempts', array('quiz' => $this->quiz->id, 'preview' => 0));

                if (!empty($attempts)) {
                    foreach ($attempts as $attempt) {
                        $attemptobj = quiz_create_attempt_handling_errors($attempt->id, $this->cm->id);
                        $result = $result && $DB->set_field('user', 'suspended', 1, array('id' => $attemptobj->get_userid()));
                    }
                }
            break;
            case ENROLLED:
                $students = get_enrolled_users($this->context, 'mod/quiz:attempt');
                if (!empty($students)) {
                    foreach ($students as $student) {
                        if ($student->suspended == 0) {
                            $result = $result && $DB->set_field('user', 'suspended', 1, array('id' => $student->id));
                        }
                    }
                }
            break;
        }
        return $result;
    }

    /**
     * Create and downlooad an Excel file with all quiz grades.
     *
     * @return boolean True on success and false on failure.
     */
    private function download_grades_file() {
        global $CFG, $DB;

        $fs = get_file_storage();
        $xlsname = $this->get_finalized_filename('-grades.xlsx');

        // Create Excel Workbook and define cell formats.
        require_once($CFG->libdir . '/excellib.class.php');
        $workbook = new MoodleExcelWorkbook($xlsname);
        $myxls = $workbook->add_worksheet();
        $format = $workbook->add_format();
        $format->set_bold(0);
        $formatbc = $workbook->add_format();
        $formatbc->set_bold(1);
        $formatbc->set_align('center');
        $rownum = 0;

        $attempts = $DB->get_records('quiz_attempts', array('quiz' => $this->quiz->id, 'preview' => 0));
        if (!empty($attempts)) {
            foreach ($attempts as $attempt) {
                // Excel row data content.
                $row = array();

                $attemptobj = quiz_create_attempt_handling_errors($attempt->id, $this->cm->id);
                $student = $DB->get_record('user', array('id' => $attemptobj->get_userid()));
                $row['firstname'] = $student->firstname;
                $row['lastname'] = $student->lastname;
                $row['idnumber'] = $student->idnumber;

                // Show marks (if the user is allowed to see marks at the moment).
                $grade = quiz_rescale_grade($attempt->sumgrades, $this->quiz, false);
                if (quiz_has_grades($this->quiz)) {
                    if ($attempt->state != mod_quiz\quiz_attempt::FINISHED) {
                        // Cannot display grade.
                        $row['grade'] = '';
                    } else if (is_null($grade)) {
                        $row['grade'] = quiz_format_grade($this->quiz, $grade);
                    } else {
                        // Now the scaled grade.
                        $a = new stdClass();
                        $a->grade = html_writer::tag('b', quiz_format_grade($this->quiz, $grade));
                        $a->maxgrade = quiz_format_grade($this->quiz, $this->quiz->grade);
                        if ($this->quiz->grade != 100) {
                            $a->percent = html_writer::tag('b', format_float(
                                $attempt->sumgrades * 100 / $this->quiz->sumgrades, 0));
                            $formattedgrade = get_string('outofpercent', 'quiz', $a);
                        } else {
                            $formattedgrade = get_string('outof', 'quiz', $a);
                        }
                        $row['grade'] = quiz_format_grade($this->quiz, $grade);
                    }
                }

                $slots = $attemptobj->get_slots();
                $maxgrades = array();

                foreach ($slots as $slot) {
                    $originalslot = $attemptobj->get_original_slot($slot);
                    $number = $attemptobj->get_question_number($originalslot);

                    $qa = $attemptobj->get_question_attempt($slot);

                    if ($slot != $originalslot) {
                        $qa->set_max_mark($attemptobj->get_question_attempt($originalslot)->get_max_mark());
                    }
 
                    if ($rownum == 0) {
                        $maxgrades[$number] = $this->quiz->grade * $qa->get_max_mark() / $this->quiz->sumgrades;
                    }

                    $grade = '-';
                    if (is_null($qa->get_fraction())) {
                        if ($qa->get_state() == question_state::$needsgrading) {
                            $grade = get_string('requiresgrading', 'question');
                        }
                    } else {
                        $grade = quiz_rescale_grade($qa->get_fraction() * $qa->get_max_mark(), $this->quiz, 'question');
                    }
                    $row[$number] = $grade;
                }

                // Define spreadsheet column headers.
                if ($rownum == 0) {
                    $colnum = 0;
                    foreach (array_keys($row) as $item) {
                        $colstr = '';
                        if ($item == 'marks') {
                            $colstr = get_string($item, 'quiz') . '/' . quiz_format_grade($this->quiz, $this->quiz->sumgrades);
                        } else if ($item == 'grade') {
                            $colstr = get_string($item, 'quiz') . '/' . quiz_format_grade($this->quiz, $this->quiz->grade);
                        } else if (is_int($item)) {
                            $colstr = get_string('qbrief', 'quiz', $item) . '/' . quiz_format_grade($this->quiz, $maxgrades[$item]);
                        } else {
                            $colstr = get_string($item);
                        }
                        $myxls->write($rownum, $colnum, $colstr, $formatbc);
                        $colnum++;
                    }
                    $rownum++;
                }

                // Insert spreadsheet values for current attempt.
                $colnum = 0;
                foreach ($row as $item) {
                    $myxls->write($rownum, $colnum, $item, $format);
                    $colnum++;
                }
                $rownum++;
            }

            // Close end download Excel file.
            $workbook->close();

            return true;
        }
        return false;
    }

    /**
     * Create and store a pdf and an Excel file with all quiz reports.
     *
     * @return boolean True on success and false on failure.
     */
    private function finalize_quiz() {
        global $CFG, $DB, $PAGE;

        $canrefinalize = get_config('theme_concorsi', 'allowrefinalize');

        $nowstr = userdate(time(), '%F-%T');

        $pdfname = $this->get_finalized_filename('.pdf');
        $fs = get_file_storage();
        $pdffile = $fs->get_file($this->context->id, $this->component, $this->finalizedarea, $this->quiz->id, '/', $pdfname);
        if (!empty($pdffile)) {
            if (!$canrefinalize) {
                return false;
            } else {
                $pdfname = $this->get_finalized_filename('-' . $nowstr . '.pdf');
            }
        }

        $xlsname = $this->get_finalized_filename('.xlsx');
        $xlsfile = $fs->get_file($this->context->id, $this->component, $this->finalizedarea, $this->quiz->id, '/', $xlsname);
        if (!empty($xlsfile)) {
            if (!$canrefinalize) {
                return false;
            } else {
                $xlsname = $this->get_finalized_filename('-' . $nowstr . '.xlsx');
            }
        }

        $tempdir = make_temp_directory('core_plugin/quiz_concorsi');

        // Create Excel Workbook and define cell formats.
        require_once('classes/extendedexcellib.class.php');
        $workbook = new ExtendedMoodleExcelWorkbook($xlsname);
        $myxls = $workbook->add_worksheet($nowstr);
        $format = $workbook->add_format();
        $format->set_bold(0);
        $formatbc = $workbook->add_format();
        $formatbc->set_bold(1);
        $formatbc->set_align('center');
        $rownum = 0;

        // Create PDF object and define header and footer.
        require_once($CFG->libdir . '/pdflib.php');
        $pdftempfilepath = $tempdir . DIRECTORY_SEPARATOR . $pdfname;
        $doc = new pdf;
        $doc->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
        $quizdata = $this->get_finalized_filename('') . '-' . $nowstr;
        $doc->SetHeaderData(null, null, null, $quizdata);
        $doc->SetFooterData(array(0, 0, 0), array(0, 0, 0));

        $doc->SetTopMargin(18);
        $doc->SetHeaderMargin(PDF_MARGIN_HEADER);
        $doc->SetFooterMargin(PDF_MARGIN_FOOTER);

        $attempts = $DB->get_records('quiz_attempts', array('quiz' => $this->quiz->id, 'preview' => 0));
        if (!empty($attempts)) {
            foreach ($attempts as $attempt) {
                // PDF data content.
                $content = '';

                // Excel row data content.
                $row = array();

                $attemptobj = quiz_create_attempt_handling_errors($attempt->id, $this->cm->id);
                $student = $DB->get_record('user', array('id' => $attemptobj->get_userid()));

                $summarydata = array();
                $summarydata['user'] = array();
                $summarydata['user']['title'] = get_string('candidate', 'quiz_concorsi');
                $summarydata['user']['content'] = fullname($student, true);
                $row['lastname'] = '';
                $row['firstname'] = '';

                $summarydata['username'] = array();
                $summarydata['username']['title'] = get_string('username');
                $summarydata['username']['content'] = $student->username;
                $row['username'] = $student->username;

                $summarydata['idnumber'] = array();
                $summarydata['idnumber']['title'] = get_string('idnumber');
                $summarydata['idnumber']['content'] = $student->idnumber;
                $row['idnumber'] = $student->idnumber;

                $summarydata['filehash'] = array();
                $summarydata['filehash']['title'] = get_string('filehash', 'quiz_concorsi');
                $idnumber = str_pad($student->idnumber, 6, '0', STR_PAD_LEFT);
                $filename = clean_param(fullname($student) . '-' . $idnumber . '.pdf', PARAM_FILE);
                $filehash = '';
                $file = $fs->get_file($this->context->id, $this->component, $this->reviewarea, $this->quiz->id, '/', $filename);
                if (!empty($file)) {
                    $filehash = $file->get_contenthash();
                } else {
                    $filename = clean_param(fullname($USER) . '-' . $idnumber . '-' . $attempt->id . '.pdf', PARAM_FILE);
                    $file = $fs->get_file($this->context->id, $this->component, $this->reviewarea, $this->quiz->id, '/', $filename);
                    if (!empty($file)) {
                        $filehash = $file->get_contenthash();
                    }
                }
                $summarydata['filehash']['content'] = $filehash;
                $row['filehash'] = $filehash;

                // Show marks (if the user is allowed to see marks at the moment).
                $grade = quiz_rescale_grade($attempt->sumgrades, $this->quiz, false);
                if (quiz_has_grades($this->quiz)) {

                    if ($attempt->state != quiz_attempt::FINISHED) {
                        // Cannot display grade.
                        echo '';
                    } else if (is_null($grade)) {
                        $summarydata['grade'] = array(
                            'title' => get_string('grade', 'quiz'),
                            'content' => quiz_format_grade($this->quiz, $grade),
                        );
                        $row['grade'] = quiz_format_grade($this->quiz, $grade);

                    } else {
                        // Show raw marks only if they are different from the grade (like on the view page).
                        if ($this->quiz->grade != $this->quiz->sumgrades) {
                            $a = new stdClass();
                            $a->grade = quiz_format_grade($this->quiz, $attempt->sumgrades);
                            $a->maxgrade = quiz_format_grade($this->quiz, $this->quiz->sumgrades);
                            $summarydata['marks'] = array(
                                'title' => get_string('marks', 'quiz'),
                                'content' => get_string('outofshort', 'quiz', $a),
                            );
                            $row['marks'] = quiz_format_grade($this->quiz, $attempt->sumgrades);
                        }

                        // Now the scaled grade.
                        $a = new stdClass();
                        $a->grade = html_writer::tag('b', quiz_format_grade($this->quiz, $grade));
                        $a->maxgrade = quiz_format_grade($this->quiz, $this->quiz->grade);
                        if ($this->quiz->grade != 100) {
                            $a->percent = html_writer::tag('b', format_float(
                                $attempt->sumgrades * 100 / $this->quiz->sumgrades, 0));
                            $formattedgrade = get_string('outofpercent', 'quiz', $a);
                        } else {
                            $formattedgrade = get_string('outof', 'quiz', $a);
                        }
                        $summarydata['grade'] = array(
                            'title' => get_string('grade', 'quiz'),
                            'content' => $formattedgrade,
                        );
                        $row['grade'] = quiz_format_grade($this->quiz, $grade);
                    }
                }

                // Feedback if there is any, and the user is allowed to see it now.
                $feedback = $attemptobj->get_overall_feedback($grade);
                if ($feedback) {
                    $summarydata['feedback'] = array(
                        'title' => get_string('feedback', 'quiz'),
                        'content' => $feedback,
                    );
                }

                $renderer = $PAGE->get_renderer('mod_quiz');
                $content .= $renderer->review_summary_table($summarydata, 0);

                $slots = $attemptobj->get_slots();

                foreach ($slots as $slot) {
                    $originalslot = $attemptobj->get_original_slot($slot);
                    $number = $attemptobj->get_question_number($originalslot);

                    $qa = $attemptobj->get_question_attempt($slot);

                    if ($slot != $originalslot) {
                        $qa->set_max_mark($attemptobj->get_question_attempt($originalslot)->get_max_mark());
                    }
                    $displayoptions = $attemptobj->get_display_options(true);

                    $content .= html_writer::tag('<h2>', get_string('questionnumber', 'quiz_concorsi', $number));
                    $content .= html_writer::tag('<pre>', rtrim($qa->get_question_summary()));
                    $content .= html_writer::tag('<h3>', get_string('answer', 'quiz_concorsi'));
                    $content .= html_writer::tag('<pre>', rtrim($qa->get_response_summary()));

                    if (is_null($qa->get_fraction())) {
                        $mark = $qa->format_max_mark($displayoptions->markdp);
                        $content .= html_writer::tag('<p>', get_string('markedoutofmax', 'question', $mark));
                    } else {
                        $grade = new stdClass();
                        $grade->mark = $qa->format_mark($displayoptions->markdp);
                        $grade->max = $qa->format_max_mark($displayoptions->markdp);
                        $content .= html_writer::tag('<p>', get_string('markoutofmax', 'question', $grade));
                    }

                    $rightanswer = rtrim($qa->get_right_answer_summary());
                    if (!empty($rightanswer)) {
                        $content .= html_writer::tag('<h3>', get_string('rightanswer', 'quiz_concorsi'));
                        $content .= html_writer::tag('<pre>', $rightanswer);
                    }

                    $manualcomment = $qa->get_current_manual_comment();
                    if (!empty($manualcomment[0])) {
                        $content .= html_writer::tag('<h3>', get_string('comment', 'question'));
                        $comment = $manualcomment[0];
                        $commentformat = $manualcomment[1];
                        html_writer::tag('<pre>', rtrim($qa->get_question()->html_to_text($coment, $commentformat)));
                    }

                    $content .= html_writer::empty_tag('<hr>', array());

                }

                // Define spreadsheet column headers.
                if ($rownum == 0) {
                    $colnum = 0;
                    foreach (array_keys($row) as $item) {
                        $colstr = '';
                        if ($item == 'marks') {
                            $colstr = get_string($item, 'quiz') . '/' . quiz_format_grade($this->quiz, $this->quiz->sumgrades);
                        } else if ($item == 'grade') {
                            $colstr = get_string($item, 'quiz') . '/' . quiz_format_grade($this->quiz, $this->quiz->grade);
                        } else if ($item == 'filehash') {
                            $colstr = get_string('filehash', 'quiz_concorsi');
                        } else {
                            $colstr = get_string($item);
                        }
                        $myxls->write($rownum, $colnum, $colstr, $formatbc);
                        $colnum++;
                    }
                    $rownum++;
                }

                // Insert spreadsheet values for current attempt.
                $colnum = 0;
                foreach ($row as $item) {
                    $myxls->write($rownum, $colnum, $item, $format);
                    $colnum++;
                }
                $rownum++;

                $doc->AddPage();
                $doc->writeHTML($content);
            }

            // Store Excel and PDF files.
            $xlstempfilepath = $tempdir . DIRECTORY_SEPARATOR . $xlsname;
            $workbook->save($tempdir);

            $doc->lastPage();
            $doc->Output($pdftempfilepath, 'F');

            if ($this->store_finalized_file($pdfname, $pdftempfilepath) &&
                    $this->store_finalized_file($xlsname, $xlstempfilepath)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Store file finalized file into Moodle filesystem.
     *
     * @param  string $filename The filename to store.
     * @param  string $filepath The filepath where the temporary file is stored.
     *
     * @return boolean True on success and false on failure.
     */
    private function store_finalized_file($filename, $filepath) {
        if (!empty($filepath) && file_exists($filepath)) {
            $fileinfo = [
                'contextid' => $this->context->id,
                'component' => $this->component,
                'filearea' => $this->finalizedarea,
                'itemid' => $this->quiz->id,
                'filepath' => '/',
                'filename' => $filename,
            ];

            $fs = get_file_storage();
            $fs->create_file_from_pathname($fileinfo, $filepath);
            unlink($filepath);

            $file = $fs->get_file($this->context->id, $this->component, $this->finalizedarea, $this->quiz->id, '/', $filename);
            if (!empty($file)) {
                return true;
            }
        }
        return false;
    }

}
