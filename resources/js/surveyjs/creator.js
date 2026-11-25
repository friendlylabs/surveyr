/**
 * The SurveyJS Creator: the form builder. Load it with <x-surveyjs entry="creator" />; page scripts read window.surveyjs.
 *   const creator = new surveyjs.SurveyCreator({ showLogicTab: true });
 *   surveyjs.renderSurveyCreator(creator, document.getElementById("creator"));
 */
import "./license.js";
import { Model } from "survey-core";
import { SurveyCreator, renderSurveyCreator } from "survey-creator-js";
import "survey-core/survey-core.css";
import "survey-creator-core/survey-creator-core.css";

window.surveyjs = Object.assign(window.surveyjs || {}, { Model, SurveyCreator, renderSurveyCreator });
