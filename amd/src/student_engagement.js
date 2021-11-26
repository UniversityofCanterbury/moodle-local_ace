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
 * Uses the local_ace webservices to create engagement charts
 *
 * @module      local_ace/student_engagement
 * @copyright   2021 University of Canterbury
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import {get_string as getString} from 'core/str';
import ModalFactory from 'core/modal_factory';
import Templates from 'core/templates';
import ModalEvents from "core/modal_events";
import ChartBuilder from 'core/chart_builder';
import ChartJSOutput from 'core/chart_output_chartjs';
import {init as filtersInit} from 'local_ace/chart_filters';

let USER_ID = {};
// Stores the chosen comparison method.
let COMPARISON_OPTION = 'average-course-engagement';
// Stores the current time method, allowing us to update the graph without supplying values.
let START_TIME = null;
let END_TIME = null;
// Toggles the retrieval of a single course vs all courses enrolleid in.
let SHOW_ALL_COURSES = false;

let COLOURS = [];

/**
 * Retrieves data from the local_ace webservice to populate an engagement graph
 *
 * @param {Object} parameters Data passed from the server.
 */
export const init = (parameters) => {
    USER_ID = parameters.userid;
    COLOURS = parameters.colours;
    filtersInit(updateGraph);

    // Hide the 'Show all courses' button on every tab except 'Overall' (course=0).
    let params = new URLSearchParams(new URL(window.location.href).search);
    if (params.has('course')) {
        if (parseInt(params.get('course')) === 0) {
            document.querySelector('#show-courses-buttons').style.display = null;
        }
    } else {
        document.querySelector('#show-courses-buttons').style.display = null;
    }

    // Setup chart comparison control.
    let chartComparisonButton = document.querySelector("#chart-comparison");
    chartComparisonButton.addEventListener("click", createChartComparisonModal);
    // Retrieve user preference and set our comparison option, then update the graph.
    getComparisonMethodPreference().then(response => {
        if (response.error) {
            displayError(response.error);
            return;
        }
        if (response.preferences[0].value !== null) {
            COMPARISON_OPTION = response.preferences[0].value;
        }
        updateGraph();
        return;
    }).catch(Notification.exception);


    document.querySelector("#show-all-courses").addEventListener("click", showAllCourses);
    document.querySelector("#show-your-course").addEventListener("click", showYourCourse);
};

const showAllCourses = function() {
    document.querySelector("#show-all-courses").style.display = 'none';
    document.querySelector("#show-your-course").style.display = null;
    SHOW_ALL_COURSES = true;
    document.querySelector("#student-engagement-legend").style.display = 'none';
    updateGraph();
};

const showYourCourse = function() {
    document.querySelector("#show-your-course").style.display = 'none';
    document.querySelector("#show-all-courses").style.display = null;
    document.querySelector("#student-engagement-legend").style.display = null;
    SHOW_ALL_COURSES = false;
    updateGraph();
};

/**
 * Creates the chart comparison modal.
 */
const createChartComparisonModal = function() {
    var modalPromise = ModalFactory.create({type: ModalFactory.types.SAVE_CANCEL});

    modalPromise.then(function(modal) {
        modal.getRoot()[0].classList.add('local_ace-slim-modal');
        modal.setTitle("Change course comparison data");
        let templatePromise = Templates.render('local_ace/chart_comparison_body', {});
        modal.setBody(templatePromise);
        modal.setSaveButtonText("Filter");

        // Check the comparison option on load.
        modal.getRoot().on(ModalEvents.bodyRendered, function() {
            document.querySelector('#comparison-' + COMPARISON_OPTION).checked = true;
            // If all courses are shown then we cannot show comparisons.
            if (SHOW_ALL_COURSES) {
                let elements = document.querySelectorAll('input[name="comparison-options"]');
                elements.forEach((ele) => {
                    ele.disabled = true;
                });
            }
        });

        // Update COMPARISON_OPTION when the modal is saved.
        modal.getRoot().on(ModalEvents.save, function() {
            let checkedElement = document.querySelector('input[name="comparison-options"]:checked');
            if (checkedElement !== null) {
                COMPARISON_OPTION = checkedElement.value;
            } else {
                COMPARISON_OPTION = 'none';
            }
            updateGraph();
            updateComparisonMethodPreference();
        });

        modal.getRoot().on(ModalEvents.hidden, () => {
            // Destroy when hidden, removes modal HTML from document.
            modal.destroy();
        });

        modal.show();

        return modal;
    }).fail(Notification.exception);
};

/**
 * Update the graph display based on values fetched from a webservice.
 *
 * @param {Number|null} startDatetime
 * @param {Number|null} endDateTime
 */
const updateGraph = (startDatetime = START_TIME, endDateTime = END_TIME) => {
    if (START_TIME !== startDatetime) {
        START_TIME = startDatetime;
    }
    if (END_TIME !== endDateTime) {
        END_TIME = endDateTime;
    }

    let url = new URL(window.location.href);
    let params = new URLSearchParams(url.search);
    let courseid = null;
    if (params.has('course')) {
        courseid = parseInt(params.get('course'));
    }
    let engagementDataPromise = getUserEngagementData(courseid, USER_ID, START_TIME, END_TIME)
        .then(function(response) {
            // Check for any errors before processing.
            if (response.error !== null) {
                displayError(response.error);
                return null;
            } else if (response.data.length === 0) {
                getString('noanalytics', 'local_ace').then((langString) => {
                    displayError(langString);
                    return;
                }).catch();
                return null;
            }

            // Populate empty fields.
            let graphData = getGraphDataPlaceholder();
            graphData.legend_options.display = SHOW_ALL_COURSES;
            // Create individual series data.
            let i = 0;
            response.data.forEach((data) => {
                let series = getSeriesPlaceholder();
                series.label = data.label;
                series.values = data.values;
                if (SHOW_ALL_COURSES || data.colour === undefined) {
                    // Choose a colour from the array and wrap around when reaching the end.
                    series.colors = [COLOURS[i % COLOURS.length]];
                    i++;
                } else {
                    series.colors = [data.colour];
                }
                series.fill = data.fill ? 1 : null;
                graphData.series.push(series);
            });
            graphData.labels = response.xlabels;
            graphData.axes.y[0].max = 100;
            graphData.axes.y[0].stepSize = 25;
            let yLabels = {};
            response.ylabels.forEach((element) => {
                yLabels[element.value] = element.label;
            });
            graphData.axes.y[0].labels = yLabels;

            return graphData;
        }).catch(function() {
            displayError("API returned an error");
        });

    engagementDataPromise.then((data) => {
        if (data === null) {
            return;
        }
        let chartArea = document.querySelector('#chart-area-engagement');
        let chartImage = chartArea.querySelector('.chart-image');
        chartImage.innerHTML = "";
        ChartBuilder.make(data).then((chart) => {
            new ChartJSOutput(chartImage, chart);
            return;
        }).catch();
        return;
    }).catch();
};

/**
 * Display an error on the page, which replaces the engagement graphs on the page.
 *
 * @param {String} langString Text displayed on the page
 */
const displayError = (langString) => {
    let chartArea = document.querySelector('#chart-area-engagement');
    let chartImage = chartArea.querySelector('.chart-image');
    chartImage.innerHTML = langString;
};

/**
 * Get analytics data for specific user and course, within a certain period and after a starting time.
 *
 * @param {Number|null} courseid Course ID
 * @param {Number} userid User ID
 * @param {Number} start Start time of analytics period in seconds
 * @param {Number} end End of analytics period in seconds
 * @param {String} comparison Comparison method
 * @returns {Promise}
 */
const getUserEngagementData = (courseid, userid, start, end, comparison = COMPARISON_OPTION) => {
    return Ajax.call([{
        methodname: 'local_ace_get_user_analytics_graph',
        args: {
            'courseid': courseid,
            'userid': userid,
            'start': start,
            'end': end,
            'comparison': comparison,
            'showallcourses': SHOW_ALL_COURSES,
        },
    }])[0];
};

/**
 * Updates the comparison method user preference.
 */
const updateComparisonMethodPreference = function() {
    let request = {
        methodname: 'core_user_update_user_preferences',
        args: {
            preferences: [
                {
                    type: 'local_ace_comparison_method',
                    value: COMPARISON_OPTION
                }
            ]
        }
    };

    Ajax.call([request])[0].fail(Notification.exception);
};

/**
 * Return a promise for the comparison method user preference.
 *
 * @returns {Promise}
 */
const getComparisonMethodPreference = function() {
    let request = {
        methodname: 'core_user_get_user_preferences',
        args: {
            name: 'local_ace_comparison_method'
        }
    };
    return Ajax.call([request])[0];
};

/**
 * Get a graph.js data object filled out with the values we need for a student engagement graph.
 *
 * @returns {Object}
 */
const getGraphDataPlaceholder = () => {
    return {
        "type": "line",
        "series": [],
        "labels": null,
        "title": null,
        "axes": {
            "x": [],
            "y": [
                {
                    "label": null,
                    "labels": {},
                    "max": null,
                    "min": 0,
                    "position": null,
                    "stepSize": null
                }
            ]
        },
        "legend_options": {
            "display": false
        },
        "config_colorset": null,
        "smooth": true
    };
};

const getSeriesPlaceholder = () => {
    return {
        "label": "",
        "labels": null,
        "type": null,
        "values": [],
        "colors": [],
        "fill": null,
        "axes": {
            "x": null,
            "y": null
        },
        "urls": [],
        "smooth": null
    };
};
