/**
 * SurveyJS Analytics: charts over a form's submissions (visualizer, reports). Load it with <x-surveyjs entry="analytics" />.
 *   const panel = new surveyjs.VisualizationPanel(new surveyjs.Model(json).getAllQuestions(), submissions);
 *   panel.render(document.getElementById("charts"));
 */
import "./license.js";
import { Model } from "survey-core";
import { VisualizationPanel } from "survey-analytics";
import "survey-analytics/survey.analytics.css";

window.surveyjs = Object.assign(window.surveyjs || {}, { Model, VisualizationPanel });
