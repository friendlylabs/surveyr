/**
 * The public form (public/form): renders the form from #publicFormConfig with the SurveyJS runner in its theme and
 * sends the answers when it's completed. SurveyJS shows "saving…", then its thank-you message or the error; the form's
 * redirect (navigateToUrl, set under Settings › Submission) is followed only once the response is stored.
 * Files go to private storage as they're picked (FormsController@upload): the answer keeps "upload:{hash}", and the
 * preview comes from the short-lived link the upload returned. Signatures stay in the answer.
 */
(function () {
  const host = document.getElementById("publicForm");
  const configEl = document.getElementById("publicFormConfig");
  if (!host || !configEl || !window.surveyjs?.Model) return;

  const config = JSON.parse(configEl.textContent);
  const survey = new surveyjs.Model(config.content);
  survey.applyTheme(surveyjs.buildTheme(config.theme));

  // a panelless form is clear so the page embedding it shows through; on a page of its own it paints the page in its
  // theme's colour instead, so it never sits on a background its theme didn't choose
  if (window.self === window.top && document.documentElement.classList.contains("pc-public-clear")) {
    document.documentElement.style.background = surveyjs.pageColour(config.theme);
  }

  // files are uploaded, not kept in the answer; dynamic panels' copies follow their template
  const toStorage = (question) => { if (question.getType() === "file") question.storeDataAsText = false; };
  survey.getAllQuestions(true).forEach((question) => {
    toStorage(question);
    if (question.getType() === "paneldynamic") question.template.questions.forEach(toStorage);
  });
  survey.onDynamicPanelAdded.add((_, options) => options.panel?.questions.forEach(toStorage));

  const previews = new Map();
  const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || "";

  /** Uploads one file; resolves to SurveyJS's {file, content} or rejects with the reason. */
  async function upload(file) {
    const body = new FormData();
    body.append("file", file);
    const response = await fetch(config.uploadUrl, {
      method: "POST",
      body,
      headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest", "X-CSRF-TOKEN": csrf() },
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok || !data.success) {
      throw new Error(data.errors?.file?.[0] || data.message || pc.t("The file couldn't be uploaded."));
    }
    previews.set(data.content, data.preview);
    return { file, content: data.content };
  }

  survey.onUploadFiles.add(async (_, options) => {
    try {
      options.callback(await Promise.all(options.files.map(upload)));
    } catch (err) {
      options.callback([], [err.message]);
    }
  });
  survey.onDownloadFile.add((_, options) => {
    const preview = previews.get(options.content);
    preview ? options.callback("success", preview) : options.callback("error");
  });

  // follow the redirect ourselves, after the response is safely stored
  const redirect = survey.navigateToUrl;
  survey.navigateToUrl = "";
  const startedAt = Date.now();

  survey.onComplete.add(async (sender, options) => {
    options.showSaveInProgress(pc.t("Sending your response…"));
    try {
      const response = await fetch(config.submitUrl, {
        method: "POST",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
        },
        body: JSON.stringify({
          data: sender.data,
          meta: { locale: sender.locale || config.locale, seconds: Math.round((Date.now() - startedAt) / 1000) },
        }),
      });
      const data = await response.json().catch(() => ({}));
      if (!response.ok || !data.success) throw new Error(data.message || "");

      options.showSaveSuccess(pc.t("Your response has been sent."));
      if (redirect) location.href = redirect;
    } catch (err) {
      options.showSaveError(err.message || pc.t("Your response couldn't be sent. Check your connection and try again."));
    }
  });

  surveyjs.renderSurvey(survey, host);
})();
