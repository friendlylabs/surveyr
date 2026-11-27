/**
 * The response visualizer's engine (libs/surveyr_analytics), for pages that summarise responses. Load it with
 * @vite('resources/js/analytics.js') beside <x-chart>; page code uses window.surveyrAnalytics.createAnalytics().
 */
import * as surveyrAnalytics from "../../libs/surveyr_analytics/src/index.js";

window.surveyrAnalytics = surveyrAnalytics;
