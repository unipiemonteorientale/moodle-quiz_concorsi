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
 * Concorsi quiz report settings file.
 *
 * @package   quiz_concorsi
 * @copyright 2024 UPO www.uniupo.it
 * @author    Roberto Pinna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    // Anonymize quiz attempts dates.
    $name = 'quiz_concorsi/anonymizedates';
    $title = get_string('anonymizedates', 'quiz_concorsi');
    $description = get_string('anonymizedates_desc', 'quiz_concorsi');
    $choices = [];
    $choices[0] = new lang_string('no');
    $choices[1] = new lang_string('clear', 'quiz_concorsi');
    $choices[2] = new lang_string('coursestartdate', 'quiz_concorsi');
    $settings->add(new admin_setting_configselect($name, $title, $description, '0', $choices));

    // Add hashed username in attempt report pdfs.
    $name = 'quiz_concorsi/usernamehash';
    $title = get_string('usernamehash', 'quiz_concorsi');
    $description = get_string('usernamehash_desc', 'quiz_concorsi');
    $yesno = [0 => new lang_string('no'), 1 => new lang_string('yes')];
    $settings->add(new admin_setting_configselect($name, $title, $description, '0', $yesno));

    $name = 'quiz_concorsi/allowrefinalize';
    $title = get_string('allowrefinalize', 'quiz_concorsi');
    $description = get_string('allowrefinalize_desc', 'quiz_concorsi');
    $yesno = [0 => new lang_string('no'), 1 => new lang_string('yes')];
    $settings->add(new admin_setting_configselect($name, $title, $description, '0', $yesno));

    $name = 'quiz_concorsi/encryptzipfiles';
    $title = get_string('encryptzipfiles', 'quiz_concorsi');
    $description = get_string('encryptzipfiles_desc', 'quiz_concorsi');
    $yesno = [0 => new lang_string('no'), 1 => new lang_string('yes')];
    $settings->add(new admin_setting_configselect($name, $title, $description, '0', $yesno));

    $name = 'quiz_concorsi/suspendmode';
    $title = get_string('suspendmode', 'quiz_concorsi');
    $description = get_string('suspendmode_desc', 'quiz_concorsi');
    $choices = [];
    $choices[0] = new lang_string('none');
    $choices[1] = new lang_string('enrolled', 'quiz_concorsi');
    $choices[2] = new lang_string('attempted', 'quiz_concorsi');
    $settings->add(new admin_setting_configselect($name, $title, $description, '0', $choices));

}
