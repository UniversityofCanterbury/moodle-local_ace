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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Bulk email all students in a report
 *
 * @module      local_ace/bulk_emails_all
 * @copyright   2021 University of Canterbury
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 import ModalFactory from 'core/modal_factory';
 import Templates from 'core/templates';
 import ModalEvents from "core/modal_events";
 import * as Str from 'core/str';
 import Ajax from 'core/ajax';
 import * as Toast from 'core/toast';
 export const init = () => {
     let strings = [
         {
             key: 'emailsend',
             component: 'local_ace'
         },
         {
             key: 'bulkemailall',
             component: 'local_ace',
         }
     ];
     let emailSend = '';
     Str.get_strings(strings).then(function(langStrings) {
         emailSend = langStrings[0];
         let emailSelected = langStrings[1];
         let templatePromise = Templates.render('local_ace/bulk_email_selected', {});
         return ModalFactory.create({
             title: emailSelected,
             body: templatePromise,
             type: ModalFactory.types.SAVE_CANCEL,
         });
     }).done(function(modal) {
         modal.getRoot()[0].classList.add('local_ace-slim-modal');
         modal.setSaveButtonText(emailSend);
         modal.getRoot().on(ModalEvents.save, function() {
             let tableuniqueid = document.querySelector('.table-dynamic').getAttribute('data-table-uniqueid');
             let reportid = tableuniqueid.split('custom-report-table-')[1];
             let subject = document.querySelector('#local_ace-email-subject').value;
             let body = document.querySelector('#local_ace-email-body').value;
             sendBulkEmailsAll(reportid, subject, body).then(response => {
                Toast.add(response.message ?? 'An error occurred', {});
             });
         });
         // Handle hidden event.
         modal.getRoot().on(ModalEvents.hidden, function() {
             // Destroy when hidden.
             modal.destroy();
         });
         // Show the modal.
         modal.show();
         return;
     }).catch(Notification.exception);
 };

 /**
  * Send bulk email to all of the users in a report.
  *
  * @param {Int} reportid
  * @param {String} subject
  * @param {String} body
  * @return {Promise}
  */
 const sendBulkEmailsAll = (reportid, subject, body) => {
     return Ajax.call([{
         methodname: 'local_ace_send_bulk_emails_all',
         args: {
             'reportid': reportid,
             'subject': subject,
             'body': body
         },
     }])[0];
 };