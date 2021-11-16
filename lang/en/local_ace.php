<?php
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
 * Plugin strings are defined here.
 *
 * @package     local_ace
 * @category    string
 * @copyright   2021 University of Canterbury
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Analytics for course engagement';
$string['privacy:metadata'] = 'The Analytics for course engagement plugin does not store any personal data.';
$string['manage'] = 'Manage analytics for course engagement';
$string['logo'] = 'Logo';
$string['due'] = 'Due date';
$string['nonapplicable'] = 'N/A';
$string['submitted'] = 'Date submitted';
$string['lastaccessed'] = 'Last accessed';
$string['lastaccessedtocourse'] = 'Last access to course';
$string['numofaccess'] = 'Number of accesses';
$string['last7'] = 'Accesses in last 7 days';
$string['last30'] = 'Accesses in last 30 days';
$string['noanalytics'] = 'No analytics were found.';
$string['noanalyticsfound'] = 'No analytics were found.';
$string['ace:viewown'] = 'View own analytics';
$string['ace:view'] = 'View analytics';
$string['averagecourseengagement'] = 'Average course engagement';
$string['yourengagement'] = 'Your engagement';
$string['studentdetailheader'] =
    'The university uses machine learning to determine how students are engaging in their courses, the following data is included when analysing student engagement.';
$string['overallengagement'] = 'Overall';
$string['userreport'] = 'Analytics for course engagement';
$string['navigationlink'] = 'Engagement analytics';
$string['userhistory'] = 'User history timeline';
$string['userhistory_desc'] = 'How much of the users history should be displayed in the user report - defined in seconds';
$string['displayperiod'] = 'Display period';
$string['displayperiod_desc'] =
    'Which analysis period to use when displaying anayltics (every 3 days, every week etc.) - defined in seconds.';
$string['colourteachercoursehistory'] = 'Course report line';
$string['colourteachercoursehistory_desc'] = 'The colour used in the line graph on the course report.';
$string['colourteachercoursehigh'] = 'Course report high';
$string['colourteachercoursehigh_desc'] = 'The colour used for the high level in the donut graph on the course report.';
$string['colourteachercoursegood'] = 'Course report medium';
$string['colourteachercoursegood_desc'] = 'The colour used for the medium level in the donut graph on the course report.';
$string['colourteachercourselow'] = 'Course report low';
$string['colourteachercourselow_desc'] = 'The colour used for the low level in the donut graph on the course report.';
$string['colourteachercoursenone'] = 'Course report none';
$string['colourteachercoursenone_desc'] = 'The colour used for the none level in the donut graph on the course report.';
$string['colourusercoursehistory'] = 'User course average';
$string['colourusercoursehistory_desc'] = 'The colour used for the average course engagment level';
$string['colouruserhistory'] = 'User average';
$string['colouruserhistory_desc'] = 'The colour used for the average user engagement level';
$string['colouractivityengagement'] = 'Activity engagement';
$string['colouractivityengagement_desc'] = 'The colour used for the activity engagement level';
$string['courseregex'] = 'Course shortname regex';
$string['courseregex_desc'] =
    'Regex to cover courses we want to be included in the analytics. The data entered here will be compared against the course shortname.';
$string['userfooter'] =
    'This graph shows you how you\'re engaging in your courses compared to your classmates. This is automatically calculated every three days by reviewing your use of Learn and Echo360 (if relevant to your courses). The more you engage with these resources, the higher your engagement will be.';
$string['high'] = 'High';
$string['medium'] = 'Medium';
$string['low'] = 'Low';
$string['none'] = 'None';
$string['averageengagement'] = 'Average engagement';
$string['getstats'] = 'Generate indicator stats';
$string['courseengagement'] = 'Course engagement';
$string['noanalyticsfoundcourse'] = 'No analytics were found for this course';
$string['showaveragecourseengagement'] = 'Show average course engagement (+/- 15%)';
$string['showoptimumcourseengagementline'] = 'Show optimum course engagement line';
$string['showtop10engagementline'] = 'Show top 10% engagement line';
$string['shownone'] = 'Show none';
$string['changegraph'] = 'Change Graph';
$string['showallcourses'] = 'Show all courses';
$string['showyourcourse'] = 'Show your course';
$string['yourengagement'] = 'Your engagement';
$string['coursefilter'] = 'Course filter';
$string['showcumulative'] = 'Show cumulative';
$string['showdailyaccess'] = 'Show daily access';

$string['privacy:metadata:local_ace'] = 'Summary of user analytics data';
$string['privacy:metadata:local_ace:userid'] = 'The Moodle userid';
$string['privacy:metadata:local_ace:starttime'] = 'The start of the analysis period';
$string['privacy:metadata:local_ace:endtime'] = 'The end of the analysis period';
$string['privacy:metadata:local_ace:value'] = 'The average indicator value for this period';

$string['emailtext'] = 'Email text';
$string['emailsubject'] = 'Email subject';
$string['emailsent'] = 'Emails have been sent to selected users';
$string['emailfailed'] = 'Unfortunately something went wrong and the emails have not sent. Please Try again';
$string['bulkemailheading'] = 'Bulk emails';
$string['bulkemailbreadcrumbs'] = 'Bulk email messages';
$string['bulkactionbuttonvalue'] = 'Email Selected';
$string['pagecontextcourse'] = 'Page course context';
$string['myenrolledcourses'] = 'My enrolled courses';
$string['allaccessible'] = 'All accessible to this user';
$string['enrolledonly'] = 'Enrolled only';

$string['entityenrolment'] = 'Enrolment';
$string['timestarted'] = 'Time start';
$string['timeend'] = 'Time end';
$string['timecreated'] = 'Time created';
$string['enrol'] = 'Enrol';
$string['role'] = 'Role';
$string['useraccess'] = 'User last accessed';

$string['sampleentitytitle'] = 'ACE samples';
$string['studentengagement'] = 'Student engagement';
$string['starttime'] = 'Start time';
$string['endtime'] = 'End time';

$string['userentitytitle'] = 'Users';
