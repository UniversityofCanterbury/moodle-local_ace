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

$string['manage'] = 'Manage analytics for course engagement';
$string['logo'] = 'Logo';
$string['due'] = 'Due date';
$string['nonapplicable'] = 'N/A';
$string['submitted'] = 'Date submitted';
$string['lastaccessed'] = 'Last accessed';
$string['lastaccessedtocourse'] = 'Last access to course';
$string['last7'] = 'Accesses in last 7 days (all logs)';
$string['last30'] = 'Accesses in last 30 days (all logs)';
$string['totalaccess'] = 'Count of all activity views (activity logs)';
$string['noanalytics'] = 'No analytics were found.';
$string['ace:viewown'] = 'View own analytics';
$string['ace:view'] = 'View analytics';
$string['ace:sendbulkemails'] = 'Send bulk emails';
$string['averagecourseengagement'] = 'Average course engagement';
$string['yourengagement'] = 'Your engagement';
$string['studentdetailheader'] =
    'The university uses machine learning to determine how students are engaging in their courses, the following data is included when analysing student engagement.';
$string['overallengagement'] = 'Overall';
$string['userreport'] = 'Analytics for course engagement';
$string['courseacedashboard'] = 'Course ACE Dashboard';
$string['myacedashboard'] = 'My ACE Dashboard';
$string['studentacedashboard'] = 'Student ACE Dashboard';
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
$string['colourlastyeardata'] = 'Last years engagement';
$string['colourlastyeardata_desc'] = 'The colour used for last years engagement level';
$string['colourfilteredengagement'] = 'Filtered engagement';
$string['colourfilteredengagement_desc'] = 'The colour used for filtered engagement level';
$string['coursemodulevisible'] = 'Visible to students';
$string['completionratefilter'] = 'Number of completions';
$string['courseregex'] = 'Course shortname regex';
$string['courseregex_desc'] =
    'Regex to cover courses we want to be included in the analytics. The data entered here will be compared against the course shortname.';
$string['courseshortnameyearregex'] = 'Course shortname year regex';
$string['courseshortnameyearregex_desc'] = 'Regex to select the year from the course shortname. Must have 3 groups, first is the prefix, second is the year, third is the suffix.';
$string['colours'] = 'Engagement line colours';
$string['colours_desc'] = 'Comma separated list of hex colour codes, preceded with a hash.';
$string['highengagementcutoff'] = 'High Engagement Cutoff';
$string['highengagementcutoff_desc'] = 'Any value(stored as 0 to 1) greater than or equal to this is counted as \'High\' engagement.';
$string['mediumengagementcutoff'] = 'Medium Engagement Cutoff';
$string['mediumengagementcutoff_desc'] = 'Any value(stored as 0 to 1) greater than or equal and below the high engagement cutoff is counted as \'Medium\' engagement.';
$string['userfooter'] =
    'This graph shows you how you\'re engaging in your courses compared to your classmates. This is automatically calculated every three days by reviewing your use of Learn and Echo360 (if relevant to your courses). The more you engage with these resources, the higher your engagement will be.';
$string['high'] = 'High';
$string['medium'] = 'Medium';
$string['low'] = 'Low';
$string['none'] = 'None';
$string['averageengagement'] = 'Average engagement';
$string['getstats'] = 'Generate indicator stats';
$string['courseengagement'] = 'Course engagement';
$string['lastyearsengagement'] = 'Last years engagement';
$string['lastyearsengagementdatealignment'] = 'Last years engagement levels dates are not exact.';
$string['filteredcourseengagement'] = 'Filtered course engagement';
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
$string['submissionratefilter'] = 'Number of submissions';
$string['acedashboard'] = 'ACE Dashboard';

$string['privacy:metadata:local_ace'] = 'Summary of user analytics data';
$string['privacy:metadata:local_ace_log_summary'] = 'User activity log summary';
$string['privacy:metadata:local_ace:userid'] = 'The Moodle userid';
$string['privacy:metadata:local_ace:starttime'] = 'The start of the analysis period';
$string['privacy:metadata:local_ace:endtime'] = 'The end of the analysis period';
$string['privacy:metadata:local_ace:value'] = 'The average indicator value for this period';
$string['privacy:metadata:local_ace:viewcount'] = 'The number of user views for this activity';
$string['privacy:metadata:preference:teacher_hidden_courses'] = 'Updates the hidden courses user preference.';
$string['privacy:metadata:preference:comparison_method'] = 'Updates the comparison method user preference.';
$string['privacy:metadata:preference:default_chart_filter'] = 'Stores the default chart filter.';
$string['privacy:metadata:courseid'] = 'Course ID';
$string['privacy:metadata:cmid'] = 'The ID of the activity or resource';
$string['privacy:metadata:userid'] = 'The userid of the user';

$string['emailsend'] = 'Send';
$string['emailtext'] = 'Email text';
$string['emailsubject'] = 'Email subject';
$string['emailsent'] = 'Emails have been sent to selected users';
$string['emailsentall'] = 'Emails have been sent to all users of this report';
$string['emailportionfailed'] = 'An error has occurred, only a portion of the emails have been sent.';
$string['emailfailed'] = 'Unfortunately something went wrong and the emails have not sent. Please try again';
$string['bulkemailall'] = 'Email All';
$string['bulkemailallselected'] = 'Email Selected';
$string['bulkemailselected'] = 'Email selected users';
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
$string['totalviews']  = 'All user views';
$string['totalviewsrecent']  = 'Recent user views';
$string['maxmodulesfilter']  = 'Max number of modules viewed to display in the recent period';
$string['totalviewsuser']  = 'Total views';
$string['filternotsince'] = 'Not since';
$string['filternotinlast'] = 'Not in last number of days';
$string['fullnamedasboardlink'] = 'User fullname with dashboard link';
$string['userdashboardurl'] = 'User dashboard url';
$string['userdashboardurl_desc'] = 'The main user dashboard url';
$string['teacherdashboardurl'] = 'Teacher dashboard url';
$string['teacherdashboardurl_desc'] = 'The main teacher dashboard url';
$string['coursedashboardurl'] = 'Course dashboard url';
$string['coursedashboardurl_desc'] = 'The main course dashboard url';
$string['coursemoduledashboardurl'] = 'Course module dashboard url';
$string['coursemoduledashboardurl_desc'] = 'The main course module dashboard url';
$string['coursemodulerecentviewduration'] = 'Course module dashboard recent view duration';
$string['coursemodulerecentviewduration_desc'] = 'How new course modules need to be to be displayed in the recent view';
$string['courseshortnamedashboardlink'] = 'Course shortname with dashboard link';
$string['courseregex'] = 'ACE course regex';
$string['moduletype'] = 'Course Module Type';
$string['courseselect'] = 'Course Selection';
$string['activitynamedashboardlink'] = 'Activity name with dashboard link';
$string['activitynamelink'] = 'Activity name with activity link';

$string['userengagementdatasource'] = 'Users Engagement';
$string['engagementlevelstitle'] = 'Engagement Level';
$string['currentengagement'] = 'Current Engagement';
$string['engagementlevelfilter'] = 'Engagement Level';

$string['useractivity'] = 'User Activity Engagement';
$string['activityengagement'] = 'Activity Engagement';
$string['pagecontextactivity'] = 'Page activity context';
$string['totalaccesses'] = 'No. of accesses';
$string['totalwrites'] = 'No. of writes';
$string['lastaccessanyuser'] = 'Last access by any user';
$string['lastaccessthisuser'] = 'Last access by this user';
$string['completionrate'] = 'Rate of students who have completed this activity';
$string['submissionrate'] = 'Rate of assignment submissions';
$string['countallusers'] = 'Number of users who have accessed this activity';
$string['countallstudents'] = 'Number of students who have accessed this activity';
$string['cachedef_coursestudentcount'] = 'This contains a cache of the total number of students in a course.';
$string['studentrole'] = 'Student role';
$string['studentrole_desc'] = 'This is the role usually used as the student role in the course, it is used in the student related reportbuilder columns';
$string['logsummary'] = 'Used to generate stats on logs table for course module views.';
$string['fullnamelogslink'] = 'User fullname linked to logs report.';
$string['groups'] = 'Groups';
$string['modulesviews'] = 'Used to generate stats on logs table for most viewed course modules';
$string['position'] = 'Activity position';
$string['averagetimespentincourse'] = 'Average time spent in course in last {$a->days} days: {$a->timespent}';
$string['averagetimespentincoursefiltered'] = 'Average time spent in course in last {$a->days} days with filter: {$a->timespent}';
$string['dedicationhistory'] = 'Dedication block timeframe';
$string['dedicationhistory_help'] = 'How long to use in duration when displaying average time spent in course under ACE graphs';
$string['totalviewsrecenthide'] = 'Total views recent (hide value)';
$string['gender'] = 'Gender';
$string['ethnicity'] = 'Ethnicity';
$string['firstinfamily'] = 'First in Whanau';
$string['programme'] = 'Programme';
$string['fullfee'] = 'Domestic/International';
$string['fullpart'] = 'Full time/Part time';
$string['schooldecile'] = 'School Decile';
$string['firstyearkaitoko'] = 'First year Kaitoko';

$string['activitycompletion'] = 'Activity Completion';
$string['completedon'] = 'Completed on';
$string['completionstate'] = 'Completion state';
