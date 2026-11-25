/**
 * The SurveyJS form library: renders a form for respondents (public runner, previews, a submission shown read-only).
 * Load it with <x-surveyjs entry="form" />; page scripts read window.surveyjs.
 *   const survey = new surveyjs.Model(json);
 *   surveyjs.renderSurvey(survey, document.getElementById("survey"));
 */
import "./license.js";
import { Model } from "survey-core";
import { renderSurvey } from "survey-js-ui";
import "survey-core/survey-core.css";

window.surveyjs = Object.assign(window.surveyjs || {}, { Model, renderSurvey });
