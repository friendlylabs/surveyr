/**
 * The Theme category of the survey's settings in the Creator's property grid (a paint bucket in the side strip). Each
 * theme choice is a property of the survey drawn by the Creator's own editors: button groups for style, layout and
 * mode, a dropdown of palettes (each with its swatch), a colour editor for a custom palette, a font dropdown and a
 * file editor for the background image. The properties are never written into the form; their values come from and
 * go to the page (`get` / `set`), which keeps them in forms.theme.
 */
import { Serializer, SvgRegistry, addIconsToThemeSet } from "survey-core";
import { PropertyGridEditorCollection, editorLocalization } from "survey-creator-core";
import { PALETTES, STYLES, FONTS, rampFrom } from "./theme.js";

/** A paint bucket, the category's icon in the side strip. */
const PAINT_BUCKET = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M10.6 2.3a1 1 0 0 1 1.4 0l8.3 8.3a1 1 0 0 1 0 1.4l-7.6 7.6a3 3 0 0 1-4.2 0l-4.1-4.1a3 3 0 0 1 0-4.2l5.6-5.6-1.3-1.3a1 1 0 0 1 1.4-1.4l.5.5Zm1.4 2.8L5.8 11.3a1 1 0 0 0-.3.7h12.1l.6-.6L12 5.1ZM20 14.5s2 2.3 2 3.5a2 2 0 0 1-4 0c0-1.2 2-3.5 2-3.5Z"/></svg>';

/** A palette's five stops as a small square swatch icon, for the palette dropdown. */
function swatch(ramp) {
  const bars = ramp.map((color, i) => `<rect x="${i * 4.8}" y="0" width="4.8" height="24" fill="${color}"/>`).join("");
  return `<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><clipPath id="c"><rect width="24" height="24" rx="4"/></clipPath><g clip-path="url(#c)">${bars}</g></svg>`;
}

/** Registers icons in both of the Creator's icon sets (it re-registers its set when its own theme changes). */
function registerIcons(icons) {
  addIconsToThemeSet("v1", icons);
  addIconsToThemeSet("v2", icons);
  SvgRegistry.registerIcons(icons);
}

/**
 * Adds the category; call it before the Creator is created. `labels` holds the page's translated strings; `get()`
 * returns the current choices and `set(key, value)` changes one.
 */
export function addThemeCategory({ labels, get, set }) {
  const t = (text) => labels[text] ?? text;
  registerIcons({
    "pg-themes-24x24": PAINT_BUCKET,
    ...Object.fromEntries(Object.entries(PALETTES).map(([key, p]) => [`surveyr-palette-${key}`, swatch(p.ramp)])),
    "surveyr-palette-custom": swatch(rampFrom("#7f5fb4").map((c, i) => (i === 2 ? "#ffffff" : c))),
  });
  editorLocalization.getLocale("").pe.tabs.themes = t("Theme");

  const choices = (entries) => entries.map(([value, text]) => ({ value, text }));
  const paletteChoices = choices([...Object.entries(PALETTES).map(([v, p]) => [v, p.label]), ["custom", t("Custom colour")]]);

  // the palette is a plain Creator dropdown whose items show their swatch (list items draw an item's icon)
  PropertyGridEditorCollection.register({
    fit: (prop) => prop.type === "surveyrpalette",
    getJSON: () => ({ type: "dropdown", choices: paletteChoices, allowClear: false, searchEnabled: false }),
    onCreated: (obj, question) => {
      question.choices.forEach((item) => {
        item.iconName = `icon-surveyr-palette-${item.value}`;
      });
    },
  });
  const properties = [
    { key: "style", type: "buttongroup", title: t("Style"), choices: choices(Object.entries(STYLES).map(([v, s]) => [v, t(s.label)])) },
    { key: "layout", type: "buttongroup", title: t("Layout"), choices: choices([["panels", t("Panels")], ["panelless", t("Panelless")]]) },
    { key: "palette", type: "surveyrpalette", title: t("Palette") },
    { key: "color", type: "color", title: t("Brand colour"), dependsOn: ["surveyrPalette"], visibleIf: () => get().palette === "custom" },
    { key: "mode", type: "buttongroup", title: t("Mode"), choices: choices([["light", t("Light")], ["dark", t("Dark")]]) },
    { key: "font", type: "dropdown", title: t("Font"), choices: choices(Object.entries(FONTS).map(([v, f]) => [v, t(f.label)])) },
    { key: "backgroundImage", type: "file", title: t("Background image"), dependsOn: ["surveyrLayout"], visibleIf: () => get().layout === "panels" },
  ];

  properties.forEach((p, index) => {
    const name = `surveyr${p.key[0].toUpperCase()}${p.key.slice(1)}`;
    if (Serializer.findProperty("survey", name)) return;
    Serializer.addProperty("survey", {
      name: `${name}:${p.type}`,
      displayName: p.title,
      category: "themes",
      visibleIndex: index,
      isSerializable: false,
      ...(p.choices ? { choices: p.choices } : {}),
      ...(p.dependsOn ? { dependsOn: p.dependsOn, visibleIf: p.visibleIf } : {}),
      onGetValue: () => get()[p.key] ?? null,
      onSetValue: (obj, value) => set(p.key, value === "" ? null : value),
    });
  });

}

