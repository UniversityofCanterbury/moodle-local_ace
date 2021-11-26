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
 * Shows the teacher course engagement graph
 *
 * @module      local_ace/teacher_course_engagement
 * @copyright   2021 University of Canterbury
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import ChartBuilder from 'core/chart_builder';
import ChartJSOutput from 'core/chart_output_chartjs';
import Notification from 'core/notification';
import ModalFactory from 'core/modal_factory';
import ModalEvents from "core/modal_events";
import ChartJS from 'core/chartjs-lazy';
import Templates from "core/templates";
import {init as filtersInit} from 'local_ace/chart_filters';

// List of course shortnames hidden on the graph.
let HIDDEN_COURSES = [];
// List of courses that could be displayed on the graph, updated after fetching engagement data.
let COURSES = [];
let COLOURS = [];

export const init = (parameters) => {
    COLOURS = parameters.colours;
    filtersInit(updateGraph);
    updateGraph(null, null);

    document.querySelector("#course-filter").addEventListener("click", courseFilter);
};

/**
 * Presents a modal for user to select courses to be shown, hides all unselected courses.
 */
const courseFilter = () => {
    var modalPromise = ModalFactory.create({type: ModalFactory.types.SAVE_CANCEL});

    modalPromise.then(function(modal) {
        modal.getRoot()[0].classList.add('local_ace-slim-modal');
        modal.setTitle("Course filter");
        let templatePromise = Templates.render('local_ace/course_filter_modal', {courses: COURSES});
        modal.setBody(templatePromise);
        modal.setSaveButtonText("Filter");

        // Set any course that is hidden to be unchecked in modal.
        modal.getRoot().on(ModalEvents.bodyRendered, function() {
            HIDDEN_COURSES.forEach((shortname) => {
                let input = document.querySelector('input[id=course-filter-' + shortname + ']');
                if (input !== null) {
                    input.checked = false;
                }
            });
        });

        // Update the hidden courses list, hiding them on the chart and legend.
        modal.getRoot().on(ModalEvents.save, function() {
            let checkedCourses = document.querySelectorAll('input[name="course-filter-options"]:not(:checked)');
            checkedCourses.forEach((ele) => {
                if (!HIDDEN_COURSES.includes(ele.value)) {
                    HIDDEN_COURSES.push(ele.value);
                }
            });
            updateGraph();
        });

        modal.getRoot().on(ModalEvents.hidden, () => {
            // Destroy when hidden, removes modal HTML from document.
            modal.destroy();
        });

        modal.show();
        return modal;
    }).fail(Notification.exception);
};

const updateGraph = (startDate, endDate) => {
    let engagementData = getTeacherCourseEngagementData(startDate, endDate).then((response) => {
        if (response.error !== null || response.series.length === 0) {
            displayError(response.error);
            return null;
        }
        let data = getGraphDataPlaceholder();
        // Reset courses list, stops us from showing courses that are no longer returned.
        COURSES = [];
        let i = 0;
        response.series.forEach((series) => {
            COURSES.push({shortname: series.label});
            let seriesData = getSeriesPlaceholder();
            seriesData.label = series.label;
            seriesData.values = series.values;
            // Choose a colour from the array and wrap around when reaching the end.
            seriesData.colors = [COLOURS[i % COLOURS.length]];
            i++;
            data.series.push(seriesData);
        });
        data.labels = response.xlabels;
        data.axes.y[0].max = 100;
        data.axes.y[0].stepSize = 25;
        let yLabels = {};
        response.ylabels.forEach((element) => {
            yLabels[element.value] = element.label;
        });
        data.axes.y[0].labels = yLabels;

        if (response.series.length > 8) {
            document.querySelector("#course-filter-wrap").style.display = null;
        }

        return data;
    }).catch(() => {
        displayError("API Error");
    });

    engagementData.then((data) => {
        if (data === null) {
            return;
        }
        let chartArea = document.querySelector('#chart-area-engagement');
        let chartImage = chartArea.querySelector('.chart-image');
        chartImage.innerHTML = "";
        ChartBuilder.make(data).then((chart) => {
            new ChartJSOutput(chartImage, chart);

            // Hides courses based on user preferences.
            getHiddenCourses().then(response => {
                if (response.error) {
                    return;
                }
                let courseList = response.preferences[0].value;
                if (courseList === null) {
                    return;
                }
                HIDDEN_COURSES.push(courseList.split(","));
                // This gets all chartjs instances on the page, there is no filtering of non teacher course engagement charts.
                ChartJS.helpers.each(ChartJS.instances, function(instance) {
                    let chart = instance.chart;
                    for (let dataset in chart.data.datasets) {
                        let datasetObject = chart.data.datasets[dataset];
                        if (HIDDEN_COURSES.includes(datasetObject.label)) {
                            datasetObject.hidden = true;
                        }
                    }
                    chart.update();
                });
                return;
            }).fail(Notification.exception);
            return;
        }).catch();
        return;
    }).catch();
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

const getGraphDataPlaceholder = () => {
    return {
        "type": "line",
        "series": [],
        "labels": [],
        "title": "Course Engagement",
        "axes": {
            "x": [],
            "y": [
                {
                    "label": null,
                    "labels": {},
                    "max": 100,
                    "min": 0,
                    "position": null,
                    "stepSize": null
                }
            ]
        },
        "legend_options": {
            "display": true,
            "position": 'right',
            "onClick": legendClickHandler
        },
        "config_colorset": null,
        "smooth": true
    };
};

/**
 * Handles clicks on dataset legends.
 *
 * @param {Object} e Event
 * @param {Object} legendItem Chart.JS Legend item
 */
const legendClickHandler = function(e, legendItem) {
    let index = legendItem.datasetIndex;
    let ci = this.chart;
    let meta = ci.getDatasetMeta(index);

    if (meta.hidden === null) {
        if (ci.data.datasets[index].hidden) {
            HIDDEN_COURSES = HIDDEN_COURSES.filter(course => course !== ci.data.datasets[index].label);
            updateHiddenCourses();
            ci.data.datasets[index].hidden = false;
        } else {
            HIDDEN_COURSES.push(ci.data.datasets[index].label);
            updateHiddenCourses();
            ci.data.datasets[index].hidden = true;
        }
    }

    ci.update();
};

/**
 * Updates the hidden courses user preference, based on the HIDDEN_COURSES array.
 */
const updateHiddenCourses = function() {
    let request = {
        methodname: 'core_user_update_user_preferences',
        args: {
            preferences: [
                {
                    type: 'local_ace_teacher_hidden_courses',
                    value: HIDDEN_COURSES.join(",")
                }
            ]
        }
    };

    Ajax.call([request])[0]
        .fail(Notification.exception);
};

/**
 * Gets the hidden courses from the user preferences.
 *
 * @returns {Promise}
 */
const getHiddenCourses = function() {
    let request = {
        methodname: 'core_user_get_user_preferences',
        args: {
            name: 'local_ace_teacher_hidden_courses'
        }
    };
    return Ajax.call([request])[0];
};

/**
 * Display an error on the page, which replaces the engagement graphs on the page.
 *
 * @param {string} langString Text displayed on the page
 */
const displayError = (langString) => {
    let chartArea = document.querySelector('#chart-area-engagement');
    let chartImage = chartArea.querySelector('.chart-image');
    chartImage.innerHTML = langString;
};

/**
 * Get teacher course analytics data
 *
 * @param {Number|null} start Start time of analytics period in seconds
 * @param {Number|null} end End of analytics period in seconds
 * @returns {Promise}
 */
const getTeacherCourseEngagementData = (start, end) => {
    return Ajax.call([{
        methodname: 'local_ace_get_teacher_course_analytics_graph',
        args: {
            'start': start,
            'end': end
        },
    }])[0];
};
