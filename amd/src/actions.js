// This file is part of Moodle - https://moodle.org/
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
 * Manage modal to confirm actions or get additional data.
 *
 * @module      quiz_concorsi/actions
 * @copyright   2023 Roberto Pinna
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import {get_string as getString} from 'core/str';

const Selectors = {
    askZipPassword: '[data-action="quiz_concorsi/ask_zip_password"]',
    password1: '[name="enteredpassword1"]',
    password2: '[name="enteredpassword2"]',
    errorMessage: '[data-action="quiz_concorsi/error_message"]',
    saveButton: '[data-action="save"]',
    zipPassword: '[name="archivepassword"]',
};

export const init = async () => {

    var passwordString = await getString('password', 'core');
    var againString = await getString('again', 'core');

    var passwordsDifferString = await getString('passwordsdiffer', 'core');

    const askPassword = await ModalFactory.create({
        type: ModalFactory.types.SAVE_CANCEL,
        title: getString('typepassword', 'quiz_concorsi'),
        body: '<label for="enteredpassword1">' + passwordString + '</label> '
            + '<input type="password" name="enteredpassword1" /><br />'
            + '<label for="enteredpassword2">' + passwordString + ' (' + againString + ')</label> '
            + '<input type="password" name="enteredpassword2" /><br />'
            + '<span data-action="quiz_concorsi/error_message" class="alert-warning"></span>',
        show: false,
        removeOnClose: false,
    });

    document.addEventListener('click', e => {
        if (e.target.closest(Selectors.askZipPassword)) {
            e.preventDefault();

            askPassword.show();

            document.querySelector(Selectors.saveButton).disabled = true;

            askPassword.getRoot().on(ModalEvents.save, () => {
                if (document.querySelector(Selectors.password1).value == document.querySelector(Selectors.password2).value) {
                    document.querySelector(Selectors.zipPassword).value = document.querySelector(Selectors.password1).value;
                    document.querySelector(Selectors.askZipPassword).parentElement.submit();
                }
            });
        }
    });

    document.addEventListener('keyup', e => {
        if (e.target.closest(Selectors.password1) || e.target.closest(Selectors.password2)) {
            document.querySelector(Selectors.saveButton).disabled = true;
            if (document.querySelector(Selectors.password1).value != document.querySelector(Selectors.password2).value) {
                document.querySelector(Selectors.errorMessage).innerText = passwordsDifferString;
                document.querySelector(Selectors.saveButton).disabled = true;
            } else {
                document.querySelector(Selectors.errorMessage).innerText = "";
                if (document.querySelector(Selectors.password1).value != '') {
                    document.querySelector(Selectors.saveButton).disabled = false;
                }
            }
        }
    });
};
