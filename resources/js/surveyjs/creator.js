/**
 * The SurveyJS Creator: the form builder. Load it with <x-surveyjs entry="creator" />; page scripts read window.surveyjs.
 *   const creator = new surveyjs.SurveyCreator({ showLogicTab: true });
 *   surveyjs.renderSurveyCreator(creator, document.getElementById("creator"));
 *   creator.applyCreatorTheme(surveyjs.creatorThemes.DefaultDark);
 */
import "./license.js";
import { Model } from "survey-core";
import { SurveyCreator, renderSurveyCreator } from "survey-creator-js";
import { DefaultLight, DefaultDark } from "survey-creator-core/themes";
import { buildTheme, normalise, themeOptions } from "./theme.js";
import { addThemeCategory } from "./theme-category.js";
import "survey-core/survey-core.css";
import "survey-creator-core/survey-creator-core.css";

window.surveyjs = Object.assign(window.surveyjs || {}, {
  Model,
  SurveyCreator,
  renderSurveyCreator,
  creatorThemes: { DefaultLight, DefaultDark },
  buildTheme,
  normaliseTheme: normalise,
  addThemeCategory,
  themeOptions,
});
