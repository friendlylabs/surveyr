/**
 * A form's preview (app/forms/preview): renders the form from #formPreviewConfig with the SurveyJS runner
 * (<x-surveyjs entry="form">) in the form's own theme (the builder's Theme panel; resources/js/surveyjs/theme.js).
 * Completing it only shows the completed page; nothing is sent anywhere.
 */
(function () {
  const host = document.getElementById("formPreview");
  const configEl = document.getElementById("formPreviewConfig");
  if (!host || !configEl || !window.surveyjs?.Model) return;

  const config = JSON.parse(configEl.textContent);
  const survey = new surveyjs.Model(config.content);
  // the page head already shows the title
  survey.showTitle = false;

  survey.applyTheme(surveyjs.buildTheme(config.theme));

  surveyjs.renderSurvey(survey, host);
})();
