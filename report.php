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

require_once($CFG->dirroot . '/mod/quiz/report/attemptsreport.php');
require_once($CFG->dirroot . '/mod/quiz/report/reportlib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/attemptlib.php');
require_once($CFG->libdir . '/pagelib.php');
require_once($CFG->libdir . '/filestorage/zip_packer.php');

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
class quiz_concorsi_report extends quiz_default_report {
    /** @var object the questions that comprise this quiz.. */
    protected $questions;
    /** @var object course module object. */
    protected $cm;
    /** @var object the quiz settings object. */
    protected $quiz;
    /** @var context the quiz context. */
    protected $context;
    /** @var students the students having attempted the quiz. */
    protected $students;

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

        // Check permissions.
        $this->context = context_module::instance($cm->id);
        require_capability('mod/quiz:grade', $this->context);

        // Start output.
        $this->print_header_and_tabs($cm, $course, $quiz, 'concorsi');

        if (!empty($quiz->timeclose) && ($quiz->timeclose < time())) {
            $itemid = $this->quiz->id;

            $fs = get_file_storage();
            $files = $fs->get_area_files($this->context->id, $this->component, $this->reviewarea, $itemid);

            if (!empty($files)) {
                $this->print_files($files, 'quiz-attempts-files');

                $finalizedfiles = $fs->get_area_files($this->context->id, $this->component, $this->finalizedarea, $itemid);
                $zipped = false;
                $finalized = false;
                if (!empty($finalizedfiles)) {
                    foreach ($finalizedfiles as $finalizedfile) {
                        if ($finalizedfile->get_filename() != '.') {
                            $zipped = $finalizedfile->get_mimetype() === 'application/zip';
                            $finalized = $finalizedfile->get_mimetype() === 'application/pdf';
                        }
                    }
                }

                $action = optional_param('action', '', PARAM_ALPHA);
                if (!empty($action)) {
                    if (!$finalized && ($action == 'finalize')) {
                        $finalized = $this->finalize_quiz();
                    }
                    if (!$zipped && ($action == 'zip')) {
                        $zipped = $this->zip_reviews($files);
                    }
                    $finalizedfiles = $fs->get_area_files($this->context->id, $this->component, $this->finalizedarea, $itemid);
                }

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
                if (!$finalized) {
                    echo $OUTPUT->single_button(
                        new moodle_url('/mod/quiz/report.php', array(
                                'id' => $cm->id,
                                'mode' => 'concorsi',
                                'action' => 'finalize')
                        ),
                        get_string('finalize', 'quiz_concorsi'),
                        'post'
                    );
                }

                if (!empty($finalizedfiles)) {
                    $this->print_files($finalizedfiles, 'quiz-finalized-files');
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
     * @param string $class CSS class name for the list.
     *
     * @return void
     */
    private function print_files($files, $class) {
        echo html_writer::start_tag('ul', array('class' => $class));
        foreach ($files as $file) {
            $filename = $file->get_filename();
            if ($filename != '.') {
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
                echo html_writer::tag('li', $downloadlink);
            }
        }
        echo html_writer::end_tag('ul');
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
     * Create and store a pdf file with all quiz reports.
     *
     * @return boolean True on success and false on failure.
     */
    private function finalize_quiz() {
        global $DB;

        $attempts = $DB->get_records('quiz_attempts', array('quiz' => $this->quiz->id, 'preview' => 0));

        if (!empty($attempts)) {
            foreach ($attempts as $attempt) {
                $attemptobj = quiz_create_attempt_handling_errors($attempt->id, $this->cm->id);
                $student = $DB->get_record('user', array('id' => $attemptobj->get_userid()));

                $summarydata = array();
                $summarydata['user'] = array();
                $summarydata['user']['title'] = get_string('candidate', 'quiz_concorsi');
                $summarydata['user']['content'] = fullname($student, true);

                $summarydata['username'] = array();
                $summarydata['username']['title'] = get_string('username');
                $summarydata['username']['content'] = $student->username;

                $summarydata['idnumber'] = array();
                $summarydata['idnumber']['title'] = get_string('idnumber');
                $summarydata['idnumber']['content'] = $student->idnumber;

                // Show marks (if the user is allowed to see marks at the moment).
                $grade = quiz_rescale_grade($attempt->sumgrades, $quiz, false);
                if ($options->marks >= question_display_options::MARK_AND_MAX && quiz_has_grades($quiz)) {

                    if ($attempt->state != quiz_attempt::FINISHED) {
                        // Cannot display grade.
                        echo '';
                    } else if (is_null($grade)) {
                        $summarydata['grade'] = array(
                            'title' => get_string('grade', 'quiz'),
                            'content' => quiz_format_grade($quiz, $grade),
                        );

                    } else {
                        // Show raw marks only if they are different from the grade (like on the view page).
                        if ($quiz->grade != $quiz->sumgrades) {
                            $a = new stdClass();
                            $a->grade = quiz_format_grade($quiz, $attempt->sumgrades);
                            $a->maxgrade = quiz_format_grade($quiz, $quiz->sumgrades);
                            $summarydata['marks'] = array(
                                'title' => get_string('marks', 'quiz'),
                                'content' => get_string('outofshort', 'quiz', $a),
                            );
                        }

                        // Now the scaled grade.
                        $a = new stdClass();
                        $a->grade = html_writer::tag('b', quiz_format_grade($quiz, $grade));
                        $a->maxgrade = quiz_format_grade($quiz, $quiz->grade);
                        if ($quiz->grade != 100) {
                            $a->percent = html_writer::tag('b', format_float(
                                $attempt->sumgrades * 100 / $quiz->sumgrades, 0));
                            $formattedgrade = get_string('outofpercent', 'quiz', $a);
                        } else {
                            $formattedgrade = get_string('outof', 'quiz', $a);
                        }
                        $summarydata['grade'] = array(
                            'title' => get_string('grade', 'quiz'),
                            'content' => $formattedgrade,
                        );
                    }
                }

            }
        }
        return false;
    }

}
