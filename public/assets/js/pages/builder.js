/**
 * The form builder (app/forms/build): mounts the SurveyJS Creator (window.surveyjs, <x-surveyjs entry="creator">) on
 * #formBuilder with the form from #formBuilderConfig, and autosaves.
 *   - Each save sends the revision it started from; the server refuses (409) when someone else saved in between, and
 *     saving stops until the page is reloaded, so nobody's changes are lost silently.
 *   - The head's status reads Saved / Saving… / Unsaved changes / Not saved; a failed save is retried with the next
 *     change, and leaving with unsaved changes asks first.
 *   - The Creator follows the app's light / dark theme and brand colour.
 */
(function () {
  const host = document.getElementById("formBuilder");
  const configEl = document.getElementById("formBuilderConfig");
  if (!host || !configEl || !window.surveyjs?.SurveyCreator) return;

  const config = JSON.parse(configEl.textContent);
  const status = document.querySelector("[data-pc-save-status]");
  const statusText = status?.querySelector("[data-pc-save-text]");
  let revision = config.revision;
  let conflict = false;
  let dirty = false;

  const labels = {
    saved: pc.t("Saved"),
    saving: pc.t("Saving…"),
    unsaved: pc.t("Unsaved changes"),
    error: pc.t("Not saved"),
    conflict: pc.t("Changed elsewhere"),
  };

  /** Shows the save state in the page head. */
  function setStatus(state) {
    if (!status) return;
    status.dataset.state = state;
    statusText.textContent = labels[state];
  }

  // the form's theme choices (forms.theme); set up first, as the Creator reads them while it's built
  let theme = surveyjs.normaliseTheme(config.theme);

  // the Theme category in the survey's settings (property grid), drawn by the Creator's own editors; it reads and
  // changes `theme`, and is registered before the Creator exists
  const themeLabels = Object.fromEntries(["Theme", "Style", "Layout", "Palette", "Brand colour", "Mode", "Font", "Background image",
    "Classic", "Brutalist", "Soft", "Minimal", "Panels", "Panelless", "Custom colour", "Light", "Dark", "Nunito Sans", "System", "Serif", "Mono"]
    .map((text) => [text, pc.t(text)]));
  surveyjs.addThemeCategory({ labels: themeLabels, get: () => theme, set: (key, value) => chooseTheme(key, value) });

  const creator = new surveyjs.SurveyCreator({
    showLogicTab: true,
    showJSONEditorTab: true,
    showTranslationTab: false,
    showThemeTab: false,
    showCreatorThemeSettings: false,
    autoSaveEnabled: true,
  });
  creator.autoSaveDelay = 800;
  creator.JSON = config.content;

  /**
   * The Creator in the app's current theme, in the app's colours: its toolbars and panels on the sheet colour, the
   * canvas behind the form on the page background, and the brand colour for its accents.
   */
  function applyTheme() {
    const dark = document.documentElement.dataset.pcTheme === "dark";
    const base = dark ? surveyjs.creatorThemes.DefaultDark : surveyjs.creatorThemes.DefaultLight;
    const styles = getComputedStyle(document.documentElement);
    const token = (name) => styles.getPropertyValue(name).trim();
    const sheet = token("--color-pc-sheet");
    const canvas = token("--color-pc-bg");
    const brand = token("--color-pc-brand");
    creator.applyCreatorTheme({
      ...base,
      cssVariables: {
        ...base.cssVariables,
        ...(brand ? { "--sjs2-color-project-brand-600": brand } : {}),
        ...(sheet ? { "--sjs2-color-bg-basic-primary": sheet } : {}),
        ...(canvas ? { "--sjs2-color-utility-surface-designer": canvas, "--sjs2-color-utility-surface-survey": canvas } : {}),
      },
    });
  }
  applyTheme();
  document.addEventListener("pc:theme", applyTheme);

  // the Preview tab shows the form without its title, as the preview page does (the page head already names it)
  creator.onSurveyInstanceCreated.add((_, options) => {
    if (options.area === "preview-tab") options.survey.showTitle = false;
  });

  creator.onModified.add(() => {
    if (conflict) return;
    dirty = true;
    setStatus("unsaved");
  });

  creator.saveSurveyFunc = async (saveNo, callback) => {
    if (conflict) {
      callback(saveNo, false);
      return;
    }
    setStatus("saving");

    try {
      const response = await fetch(config.saveUrl, {
        method: "POST",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
        },
        body: JSON.stringify({ content: creator.JSON, revision }),
      });
      const data = await response.json().catch(() => ({}));

      if (response.status === 409) {
        conflict = true;
        setStatus("conflict");
        callback(saveNo, false);
        pcDialog
          .confirm({
            title: pc.t("This form changed elsewhere"),
            text: data.message || "",
            icon: "warning",
            confirmButtonText: pc.t("Reload"),
          })
          .then(({ isConfirmed }) => {
            if (isConfirmed) {
              dirty = false;
              location.reload();
            }
          });
        return;
      }

      if (response.status === 419) {
        setStatus("error");
        toast.error({ message: pc.t("Your session expired. Reload the page and try again.") });
        callback(saveNo, false);
        return;
      }
      if (!response.ok || !data.success) throw new Error(data.message || "save");

      revision = data.revision;
      dirty = false;
      setStatus("saved");
      if (data.title) {
        const heading = document.querySelector(".pc-page-title");
        if (heading) heading.textContent = data.title;
      }
      callback(saveNo, true);
    } catch (err) {
      // offline or a server error: the next change saves again
      setStatus("error");
      callback(saveNo, false);
    }
  };

  // leaving with changes that haven't reached the server asks first
  window.addEventListener("beforeunload", (e) => {
    if (dirty || status?.dataset.state === "saving") {
      e.preventDefault();
      e.returnValue = "";
    }
  });

  /** Posts a request with the app's headers; resolves to [response, body]. */
  async function post(url, body) {
    const isForm = body instanceof FormData;
    const response = await fetch(url, {
      method: "POST",
      headers: {
        Accept: "application/json",
        ...(isForm ? {} : { "Content-Type": "application/json" }),
        "X-Requested-With": "XMLHttpRequest",
        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || "",
      },
      body: isForm ? body : JSON.stringify(body),
    });
    return [response, await response.json().catch(() => ({}))];
  }

  /** Uploads one image for the form; resolves to its URL. */
  async function upload(file) {
    const body = new FormData();
    body.append("file", file);
    const [response, data] = await post(config.assetUrl, body);
    if (!response.ok || !data.url) throw new Error(data.errors?.file?.[0] || data.message || pc.t("Couldn't upload the image."));
    return data.url;
  }

  // images (logo, pictures in questions) go to the server instead of into the form as text
  creator.onUploadFile.add(async (_, options) => {
    try {
      const url = await upload(options.files[0]);
      options.callback("success", url);
    } catch (err) {
      toast.error({ message: err.message });
      options.callback("error");
    }
  });

  /*
   * Theme: style, layout, palette (or a custom colour), mode, font and a background image (resources/js/surveyjs/
   * theme.js), chosen in the Theme category. Each change applies to the Creator at once (its Preview tab shows the
   * form in it) and saves a moment later.
   */

  /** Applies the theme to the Creator; its Preview tab shows the form in it (the Designer keeps its editing look). */
  function applyFormTheme() {
    creator.theme = surveyjs.buildTheme(theme);
  }
  applyFormTheme();

  const saveTheme = pc.debounce(storeTheme, 500);

  async function storeTheme() {
    setStatus("saving");
    try {
      const [response, data] = await post(config.themeUrl, theme);
      if (!response.ok) throw new Error(Object.values(data.errors || {})[0]?.[0] || data.message || "theme");
      setStatus(dirty ? "unsaved" : "saved");
    } catch (err) {
      setStatus("error");
      toast.error({ message: err.message === "theme" ? pc.t("Couldn't save the theme. Try again.") : err.message });
    }
  }

  /** Changes one choice (a custom palette starts from the brand green), applies it and saves. */
  function chooseTheme(key, value) {
    const next = { ...theme, [key]: value };
    if (key === "palette" && value === "custom" && !next.color) next.color = "#19b394";
    theme = surveyjs.normaliseTheme(next);
    applyFormTheme();
    saveTheme();
  }

  surveyjs.renderSurveyCreator(creator, host);
})();
