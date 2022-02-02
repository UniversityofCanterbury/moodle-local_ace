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
 * Provides the chart filters for engagement graphs
 *
 * @module      local_ace/chart_filters
 * @copyright   2021 University of Canterbury
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Litepicker from 'local_ace/litepicker';
import Ajax from 'core/ajax';

const FILTER_ACTIVE = "active";

const Selectors = {
    chartFilterOptions: "#chart-filter-options",
    courseToDate: "#course-to-date",
    last12Days: "#last-12-days",
    dateRange: "#date-range",
};

let updateFunc = null;

/**
 * Retrieves data from the local_ace webservice to populate an engagement graph
 *
 * @param {Function} providedFunc graph function
 */
export const init = (providedFunc) => {
    updateFunc = providedFunc;
    // Set default filter, update display, set handlers
    let filter = getActiveFilter();
    if (filter === null) {
        // Get the user preference, attempt to set the filter. Fall back to the course to date filter.
        getChartFilterPreference().then(response => {
            if (response.error) {
                return;
            }
            if (response.preferences[0].value !== null) {
                // Set the filter based on the filter node that matches the given filter ID.
                getFilterNodes().forEach((element) => {
                    if (element.id === response.preferences[0].value) {
                        if (element.id === 'last-12-days') {
                            set12DaysFilter();
                        } else if (element.id === 'course-to-date') {
                            setCourseToDateFilter();
                        }
                    }
                });
            }
        }).then(() => {
            // If setting via user preference fails we set the default.
            if (getActiveFilter() === null) {
                setCourseToDateFilter();
            }
        });
    }

    setupFilters();
};


/**
 * Set course to date filter.
 */
const setCourseToDateFilter = () => {
    let filter = document.querySelector(Selectors.courseToDate);
    setActiveFilter(filter);
    updateFunc(null, null);
};

/**
 * Set the last 12 days filter.
 */
const set12DaysFilter = () => {
    let filter = document.querySelector(Selectors.last12Days);
    setActiveFilter(filter);
    let date = new Date();
    date.setDate(date.getDate() - 12);
    let val = date.getTime() / 1000;
    updateFunc(val.toFixed(0), null);
};

/**
 * Set the active filter.
 *
 * @param {Element} suppliedFilter
 */
const setActiveFilter = (suppliedFilter) => {
    getFilterNodes().forEach((filter) => {
        if (filter === suppliedFilter) {
            filter.dataset.filter = FILTER_ACTIVE;

            // We can't store the date range as a user preference because of the extra params required.
            if (filter.id !== 'date-range') {
                updateChartFilterPreference(filter.id);
            }
        } else {
            filter.dataset.filter = null;
        }
        updateFilterDisplay(filter);
    });
};

/**
 * Set up the click/change listeners on the filter buttons.
 * When detected set the active filter and pass the new date through to the graph.
 */
const setupFilters = () => {
    let filtersNode = document.querySelector(Selectors.chartFilterOptions);

    let courseToDateFilter = filtersNode.querySelector(Selectors.courseToDate);
    courseToDateFilter.addEventListener("click", () => {
        setCourseToDateFilter();
        picker.clearSelection();
    });

    let last12DaysFilter = filtersNode.querySelector(Selectors.last12Days);
    last12DaysFilter.addEventListener("click", () => {
        set12DaysFilter();
        picker.clearSelection();
    });

    let dateRangeFilter = filtersNode.querySelector(Selectors.dateRange);
    let picker = new Litepicker({
        format: 'DD-MM-YYYY',
        element: dateRangeFilter,
        singleMode: false,
        splitView: false,
        setup: (picker) => {
            picker.on('selected', (date1, date2) => {
                if (date1 === undefined || date2 === undefined) {
                    return;
                }
                setActiveFilter(dateRangeFilter);
                updateFunc(date1.timestamp() / 1000, date2.timestamp() / 1000);
            });
        }
    });
    picker.clearSelection();
};

/**
 * Update the filter colours to display which is active.
 *
 * @param {Element} filter
 */
const updateFilterDisplay = (filter) => {
    if (filter.dataset.filter === FILTER_ACTIVE) {
        filter.classList.add("btn-primary");
        filter.classList.remove("btn-secondary");
    } else {
        filter.classList.add("btn-secondary");
        filter.classList.remove("btn-primary");
    }
};

/**
 * Get the active filter DOM element.
 *
 * @returns {null|Element}
 */
const getActiveFilter = () => {
    let filters = getFilterNodes();
    let filter = filters.find(filter => filter.dataset.filter === FILTER_ACTIVE);
    if (filter !== undefined) {
        return filter;
    }
    return null;
};

/**
 * Get the DOM element of chart filters on the page.
 *
 * @returns {[Element]}
 */
const getFilterNodes = () => {
    let filtersNode = document.querySelector(Selectors.chartFilterOptions);
    return [
        filtersNode.querySelector(Selectors.courseToDate),
        filtersNode.querySelector(Selectors.last12Days),
        filtersNode.querySelector(Selectors.dateRange)
    ];
};

/**
 * Updates the comparison method user preference.
 *
 * @param {string} activeFilter Filter ID
 */
const updateChartFilterPreference = function(activeFilter) {
    let request = {
        methodname: 'core_user_update_user_preferences',
        args: {
            preferences: [
                {
                    type: 'local_ace_default_chart_filter',
                    value: activeFilter
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
const getChartFilterPreference = function() {
    let request = {
        methodname: 'core_user_get_user_preferences',
        args: {
            name: 'local_ace_default_chart_filter'
        }
    };
    return Ajax.call([request])[0];
};
