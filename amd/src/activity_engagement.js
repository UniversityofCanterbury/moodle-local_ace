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
 * Shows the activity engagement graph
 *
 * @module      local_ace/activity_engagement
 * @copyright   2021 University of Canterbury
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import ChartBuilder from 'core/chart_builder';
import ChartJSOutput from 'core/chart_output_chartjs';
import {init as filtersInit} from 'local_ace/chart_filters';

// The course module ID that we are showing engagement data for.
let CMID = 0;
// If true we show the cumulative graph.
let CUMULATIVE = false;
// Stores the current time method, allowing us to update the graph without supplying values.
let START_TIME = null;
let END_TIME = null;
// Colour of the activity engagement line.
let COLOUR_ACTIVITY_ENGAGEMENT = null;

/**
 * Initialise the course engagement graph
 *
 * @param {Object} parameters Data passed from the server.
 */
export const init = (parameters) => {
    if (CMID !== 0) {
        return;
    }
    COLOUR_ACTIVITY_ENGAGEMENT = parameters.colouractivityengagement;
    CMID = parameters.cmid;
    filtersInit(updateGraph);

    document.querySelector('#show-cumulative').addEventListener('click', showCumulativeGraph);
    document.querySelector('#show-daily-access').addEventListener('click', showDaily);
};

/**
 * Show cumulative engagement data.
 */
const showCumulativeGraph = () => {
    CUMULATIVE = true;
    updateGraph();
    document.querySelector('#show-cumulative').style.display = 'none';
    document.querySelector('#show-daily-access').style.display = null;
};

/**
 * Show daily engagement data.
 */
const showDaily = () => {
    CUMULATIVE = false;
    updateGraph();
    document.querySelector('#show-cumulative').style.display = null;
    document.querySelector('#show-daily-access').style.display = 'none';
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

    let engagementData = getActivityEngagementData(startDatetime, endDateTime).then((response) => {
        if (response.error !== null || response.series.length === 0) {
            displayError(response.error);
            return null;
        }
        let data = getGraphDataPlaceholder();
        data.series[0].values = response.series;
        data.labels = response.xlabels;
        data.axes.y[0].max = response.max;
        data.axes.y[0].stepSize = response.stepsize;
        let yLabels = {};
        response.ylabels.forEach((element) => {
            yLabels[element.value] = element.label;
        });
        data.axes.y[0].labels = yLabels;

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
            return;
        }).catch();
        return;
    }).catch();
};

const getGraphDataPlaceholder = () => {
    return {
        "type": "line",
        "series": [
            {
                "label": "Activity Engagement",
                "labels": null,
                "type": null,
                "values": null,
                "colors": [COLOUR_ACTIVITY_ENGAGEMENT],
                "fill": null,
                "axes": {
                    "x": null,
                    "y": null
                },
                "urls": [],
                "smooth": null
            },
        ],
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

/**
 * Get activity engagement data
 *
 * @param {Number|null} start Start time of analytics period in seconds
 * @param {Number|null} end End of analytics period in seconds
 * @returns {Promise}
 */
const getActivityEngagementData = (start, end) => {
    return Ajax.call([{
        methodname: 'local_ace_get_activity_analytics_graph',
        args: {
            'cmid': CMID,
            'start': start,
            'end': end,
            'cumulative': CUMULATIVE
        },
    }])[0];
};
