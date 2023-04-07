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
 * Extended Excel writer abstraction layer.
 *
 * @package   quiz_concorsi
 * @copyright UPO <https://www.uniupo.it>
 * @author    Roberto Pinna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/phpspreadsheet/vendor/autoload.php");

use \PhpOffice\PhpSpreadsheet\IOFactory;

require_once($CFG->libdir . '/excellib.class.php');

/**
 * Extend Excel write for Moodle Workbook.
 *
 * @package   quiz_concorsi
 * @copyright UPO <https://www.uniupo.it>
 * @author    Roberto Pinna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ExtendedMoodleExcelWorkbook extends MoodleExcelWorkbook {

    /**
     * Save the Moodle Workbook
     *
     * @param string $tempdir The temporary directory where save workbook file
     * @return void
     */
    public function save($tempdir) {
        foreach ($this->objspreadsheet->getAllSheets() as $sheet) {
            $sheet->setSelectedCells('A1');
        }
        $this->objspreadsheet->setActiveSheetIndex(0);

        $filename = preg_replace('/\.xlsx?$/i', '', $this->filename);
        $filename = $filename.'.xlsx';

        $objwriter = IOFactory::createWriter($this->objspreadsheet, $this->type);
        $objwriter->save($tempdir . DIRECTORY_SEPARATOR . $filename);
    }

}
