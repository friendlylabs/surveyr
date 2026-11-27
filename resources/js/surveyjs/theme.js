/**
 * Form themes: turns the builder's theme choices (forms.theme) into a SurveyJS theme, so the builder, the preview and
 * the public form all draw a form the same way. Bundled into both the form and creator entries; page scripts read
 * window.surveyjs.buildTheme / themeOptions.
 *   survey.applyTheme(surveyjs.buildTheme({ style: "brutalist", layout: "panels", palette: "ocean", mode: "light" }));
 * Choices: style (classic | brutalist | soft | minimal), layout (panels | panelless), palette (a key of PALETTES, or
 * "custom" with color: "#rrggbb"), mode (light | dark), font (a key of FONTS), backgroundImage (a URL, panels only).
 */

import { DefaultLight, DefaultDark, DefaultLightPanelless, DefaultDarkPanelless } from "survey-core/themes";
import "./theme.css";

/** Five stops each, darkest to lightest: ink, mid, accent, light, tint. */
export const PALETTES = {
  surveyr: { label: "Surveyr", ramp: ["#0e3b32", "#13705d", "#19b394", "#7fd8c3", "#e6f7f3"] },
  crimson: { label: "Crimson", ramp: ["#5a1418", "#9b1c20", "#d4262c", "#f6888c", "#fdeeee"] },
  ember: { label: "Ember", ramp: ["#5c2c1d", "#a3532a", "#e6852f", "#fdc07f", "#fff2e6"] },
  saffron: { label: "Saffron", ramp: ["#463718", "#7a5d26", "#c99a17", "#f7e48a", "#fdf8e1"] },
  lagoon: { label: "Lagoon", ramp: ["#24464a", "#3d7e81", "#36a29c", "#a9dfe4", "#eef8fa"] },
  ocean: { label: "Ocean", ramp: ["#233a54", "#2f5f92", "#3d88c8", "#9ccaf0", "#e7f1fb"] },
  violet: { label: "Violet", ramp: ["#382160", "#5d3f9c", "#7f5fb4", "#c7aed6", "#f5e9f2"] },
  berry: { label: "Berry", ramp: ["#4a1028", "#99194c", "#d2335f", "#f6a6bf", "#fde9f1"] },
  slate: { label: "Slate", ramp: ["#1f2633", "#465264", "#5c6879", "#b9c0cc", "#f2f3f7"] },
};

/** The form's shape: corner radius, border weight, hard offset shadows, underline-only fields. */
export const STYLES = {
  classic: { label: "Classic", radius: 8, border: 1 },
  brutalist: { label: "Brutalist", radius: 0, border: 2, hard: true },
  soft: { label: "Soft", radius: 16, border: 0 },
  minimal: { label: "Minimal", radius: 0, border: 0, underline: true },
};

/** Font stacks; none loads anything from another site. */
export const FONTS = {
  default: { label: "Nunito Sans", stack: '"Nunito Sans", ui-sans-serif, system-ui, sans-serif' },
  system: { label: "System", stack: 'ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif' },
  serif: { label: "Serif", stack: 'Georgia, Cambria, "Times New Roman", serif' },
  mono: { label: "Mono", stack: 'ui-monospace, "SFMono-Regular", Menlo, Consolas, monospace' },
};

export const DEFAULTS = { style: "classic", layout: "panels", palette: "surveyr", mode: "light", font: "default" };

const HEX = /^#[0-9a-f]{6}$/i;

/** "#rrggbb" → [r, g, b]. */
function rgb(hex) {
  return [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16));
}

/** A colour with an alpha, as SurveyJS writes them. */
function rgba(hex, alpha) {
  const [r, g, b] = rgb(hex);
  return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

/** [h, s, l] (0–360, 0–1, 0–1) → "#rrggbb". */
function fromHsl(h, s, l) {
  const k = (n) => (n + h / 30) % 12;
  const a = s * Math.min(l, 1 - l);
  const f = (n) => l - a * Math.max(-1, Math.min(k(n) - 3, Math.min(9 - k(n), 1)));
  return "#" + [f(0), f(8), f(4)].map((x) => Math.round(x * 255).toString(16).padStart(2, "0")).join("");
}

/** "#rrggbb" → [h, s, l]. */
function toHsl(hex) {
  const [r, g, b] = rgb(hex).map((x) => x / 255);
  const max = Math.max(r, g, b);
  const min = Math.min(r, g, b);
  const l = (max + min) / 2;
  if (max === min) return [0, 0, l];
  const d = max - min;
  const s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
  const h = max === r ? (g - b) / d + (g < b ? 6 : 0) : max === g ? (b - r) / d + 2 : (r - g) / d + 4;
  return [h * 60, s, l];
}

/** A five-stop ramp around one brand colour, which stays the accent. */
export function rampFrom(hex) {
  const [h, s] = toHsl(hex);
  return [fromHsl(h, s * 0.55, 0.16), fromHsl(h, s * 0.6, 0.32), hex.toLowerCase(), fromHsl(h, s * 0.75, 0.78), fromHsl(h, s * 0.6, 0.96)];
}

/** The choices, each one known (unknown or missing ones fall back to the defaults). */
export function normalise(choices = {}) {
  const pick = (key, table) => (Object.hasOwn(table, choices?.[key]) ? choices[key] : DEFAULTS[key]);
  const custom = choices?.palette === "custom" && HEX.test(choices?.color || "");
  return {
    style: pick("style", STYLES),
    layout: ["panels", "panelless"].includes(choices?.layout) ? choices.layout : DEFAULTS.layout,
    palette: custom ? "custom" : pick("palette", PALETTES),
    color: custom ? choices.color.toLowerCase() : null,
    mode: choices?.mode === "dark" ? "dark" : "light",
    font: pick("font", FONTS),
    backgroundImage: typeof choices?.backgroundImage === "string" && choices.backgroundImage ? choices.backgroundImage : null,
  };
}

/**
 * The colours every style takes from the palette and mode: the accent (buttons, selection, focus), the text, and the
 * page behind the question panels (clear in the panelless layout, so a host page shows through).
 */
function paletteVariables(c, ramp) {
  const [ink, mid, accent, light, tint] = ramp;
  const dark = c.mode === "dark";
  const primary = dark ? light : accent;
  const vars = {
    "--sjs-font-family": FONTS[c.font].stack,
    "--sjs-primary-backcolor": primary,
    "--sjs-primary-backcolor-light": rgba(primary, 0.1),
    "--sjs-primary-backcolor-dark": dark ? accent : mid,
    "--sjs-primary-forecolor": dark ? ink : "#ffffff",
    "--sjs-primary-forecolor-light": "rgba(255, 255, 255, 0.25)",
    "--sjs-special-green": primary,
    "--sjs-special-green-light": rgba(primary, 0.1),
  };
  if (c.layout === "panelless") vars["--sjs-general-backcolor-dim"] = "transparent";
  else if (!dark) vars["--sjs-general-backcolor-dim"] = tint;
  return vars;
}

/** The SurveyJS theme (ITheme) for the choices. */
export function buildTheme(choices = {}) {
  const c = normalise(choices);
  const ramp = c.palette === "custom" ? rampFrom(c.color) : PALETTES[c.palette].ramp;
  const panelless = c.layout === "panelless";
  const cssVariables = c.style === "classic" ? classicVariables(c, ramp) : styledVariables(c, ramp);

  // panelless: the title band is clear too, so it sits on the same background as the questions (theme.css)
  if (panelless) cssVariables["--surveyr-header-backcolor"] = "transparent";

  const theme = { themeName: `surveyr-${c.style}`, colorPalette: c.mode, isPanelless: panelless, cssVariables };
  if (c.backgroundImage && !panelless) {
    Object.assign(theme, { backgroundImage: c.backgroundImage, backgroundImageFit: "cover", backgroundImageAttachment: "scroll", backgroundOpacity: 1 });
  }

  return theme;
}

/** Classic is SurveyJS's own Default theme (with or without panels, light or dark) in the palette's colours. */
function classicVariables(c, ramp) {
  const base = c.mode === "dark"
    ? (c.layout === "panelless" ? DefaultDarkPanelless : DefaultDark)
    : (c.layout === "panelless" ? DefaultLightPanelless : DefaultLight);

  // the Creator's preview look: rounder question panels and fields than the stock 4px
  return {
    ...base.cssVariables,
    "--sjs-corner-radius": "6px",
    "--sjs-questionpanel-cornerRadius": "12px",
    "--sjs-editorpanel-cornerRadius": "6px",
    ...paletteVariables(c, ramp),
  };
}

/**
 * Brutalist, Soft and Minimal: each question on its own panel (or, panelless, straight on the page) with the style's
 * corners, edges and shadows. Borders are drawn as inset shadows in SurveyJS.
 */
function styledVariables(c, ramp) {
  const style = STYLES[c.style];
  const [ink, mid, , , tint] = ramp;
  const dark = c.mode === "dark";
  const panelless = c.layout === "panelless";

  const fore = dark ? "#f2f3f5" : ink;
  const panel = dark ? "#20232a" : "#ffffff";
  // edges are a wash of the text colour, so they show on white, tinted and host-page backgrounds alike
  const edge = style.hard ? fore : dark ? "rgba(255, 255, 255, 0.24)" : rgba(ink, 0.28);
  // a field needs a fill to be seen on a clear (panelless) form, whatever page it sits on
  const fieldFill = style.underline
    ? "transparent"
    : panelless ? (dark ? "rgba(255, 255, 255, 0.07)" : rgba(ink, 0.05))
      : dark ? "#2a2e37" : c.style === "soft" ? tint : "#ffffff";

  const fieldBorder = style.underline
    ? `inset 0px -2px 0px 0px ${edge}`
    : style.border ? `inset 0px 0px 0px ${style.border}px ${edge}` : `inset 0px 0px 0px 1px ${dark ? "rgba(255, 255, 255, 0.12)" : rgba(ink, 0.1)}`;
  const panelShadow = style.hard
    ? `0px 0px 0px 2px ${fore}, 4px 4px 0px 0px ${fore}`
    : c.style === "soft" ? `0px 8px 24px -10px ${dark ? "rgba(0, 0, 0, 0.6)" : "rgba(0, 0, 0, 0.2)"}` : "0px 0px 0px 0px transparent";

  return {
    "--sjs-corner-radius": `${style.radius}px`,
    "--sjs-base-unit": "8px",
    // stays solid: dropdown lists, pop-ups and menus are drawn on it
    "--sjs-general-backcolor": panel,
    "--sjs-general-backcolor-dark": dark ? "#2f343e" : rgba(ink, 0.05),
    "--sjs-general-backcolor-dim": dark ? "#16181d" : tint,
    "--sjs-general-backcolor-dim-light": fieldFill,
    "--sjs-general-backcolor-dim-dark": dark ? "#2f343e" : rgba(ink, 0.06),
    "--sjs-general-forecolor": fore,
    "--sjs-general-forecolor-light": dark ? "rgba(242, 243, 245, 0.6)" : rgba(mid, 0.85),
    "--sjs-general-dim-forecolor": fore,
    "--sjs-general-dim-forecolor-light": dark ? "rgba(242, 243, 245, 0.6)" : rgba(mid, 0.85),
    "--sjs-questionpanel-backcolor": panelless ? "transparent" : panel,
    "--sjs-editorpanel-backcolor": fieldFill,
    "--sjs-editor-background": fieldFill,
    "--sjs-border-light": dark ? "rgba(255, 255, 255, 0.08)" : rgba(ink, 0.1),
    "--sjs-border-default": edge,
    "--sjs-border-inside": dark ? "rgba(255, 255, 255, 0.16)" : rgba(ink, 0.16),
    "--sjs-shadow-small": panelless && !style.hard ? "0px 0px 0px 0px transparent" : panelShadow,
    "--sjs-shadow-small-reset": "0px 0px 0px 0px transparent",
    "--sjs-shadow-medium": style.hard ? `4px 4px 0px 0px ${fore}` : "0px 2px 6px 0px rgba(0, 0, 0, 0.1)",
    "--sjs-shadow-large": style.hard ? `6px 6px 0px 0px ${fore}` : "0px 8px 16px 0px rgba(0, 0, 0, 0.1)",
    "--sjs-shadow-inner": fieldBorder,
    "--sjs-shadow-inner-reset": "inset 0px 0px 0px 0px transparent",
    // radios, checkboxes and the yes / no switch keep a whole ring when the fields are only underlined (theme.css)
    ...(style.underline ? { "--surveyr-decorator-shadow": `inset 0px 0px 0px 1px ${edge}` } : {}),
    // square styles square the pill-shaped controls too: the yes / no switch, rating buttons, ranking items
    ...(style.radius === 0 ? { "--surveyr-control-radius": "0px" } : {}),
    ...paletteVariables(c, ramp),
  };
}

/**
 * The colour behind the form for the choices: the page colour of the same theme with panels. A panelless form shown on
 * a page of its own (not embedded) paints the page with it, so it never sits on a colour the theme didn't choose.
 */
export function pageColour(choices = {}) {
  return buildTheme({ ...normalise(choices), layout: "panels", backgroundImage: null }).cssVariables["--sjs-general-backcolor-dim"];
}

/** What the picker lists. */
export const themeOptions = { palettes: PALETTES, styles: STYLES, fonts: FONTS, defaults: DEFAULTS };
