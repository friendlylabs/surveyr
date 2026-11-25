/**
 * pc-theme.js — light / dark theme.
 * The choice is kept in localStorage `pc-theme` (light | dark; unset means follow the OS) and applied as
 * data-pc-theme="light|dark" on <html>; app.css swaps its colour tokens on that. <x-theme-boot /> applies it
 * before first paint, so pages never flash the wrong theme; this file follows the OS until a choice is made
 * and wires the toggles.
 * Markup contract (<x-theme-picker />):
 *   <button type="button" data-pc-trigger="theme-toggle" aria-label="Switch to dark theme">
 *     <i class="ph ph-moon pc-theme-to-dark"></i><i class="ph ph-sun pc-theme-to-light"></i>
 *   </button>
 * A toggle flips whatever is showing, the OS's theme included: on a dark system the first click goes light.
 * Which icon shows is pure CSS on data-pc-theme (moon while light, sun while dark); the label is kept in step here.
 * The switch is animated where the browser has view transitions: the new theme grows in a circle from the toggle.
 * JS API: pcTheme.toggle(triggerEl?) / set('light' | 'dark' | 'system') / choice() / resolved()
 * Events: `pc:theme` (detail: { choice, theme }) on document.
 */
(function () {
  const KEY = "pc-theme";
  const CHOICES = ["light", "dark", "system"];
  const media = matchMedia("(prefers-color-scheme: dark)");

  function choice() {
    try {
      const saved = localStorage.getItem(KEY);
      return CHOICES.includes(saved) ? saved : "system";
    } catch (e) {
      return "system";
    }
  }

  function resolved(value = choice()) {
    if (value === "system") return media.matches ? "dark" : "light";
    return value;
  }

  function render(value, theme) {
    document.documentElement.dataset.pcTheme = theme;
    const label = theme === "dark" ? pc.t("Switch to light theme") : pc.t("Switch to dark theme");
    document.querySelectorAll('[data-pc-trigger="theme-toggle"]').forEach((el) => {
      el.setAttribute("aria-label", label);
      if (el.hasAttribute("data-pc-tooltip")) el.setAttribute("data-pc-tooltip", theme === "dark" ? pc.t("Light theme") : pc.t("Dark theme"));
    });
    document.dispatchEvent(new CustomEvent("pc:theme", { detail: { choice: value, theme } }));
  }

  function apply() {
    const value = choice();
    render(value, resolved(value));
  }

  function set(value) {
    if (!CHOICES.includes(value)) return;
    try {
      if (value === "system") localStorage.removeItem(KEY);
      else localStorage.setItem(KEY, value);
    } catch (e) {
      // storage blocked: the choice lasts for this page only
      return render(value, resolved(value));
    }
    apply();
  }

  /**
   * The opposite of what is showing now, whether that came from a saved choice or the OS. With a trigger element, the
   * new theme grows out of it in a circle (a view transition); without support, or with reduced motion, it just swaps.
   */
  function toggle(trigger = null) {
    const next = document.documentElement.dataset.pcTheme === "dark" ? "light" : "dark";
    const reduced = matchMedia("(prefers-reduced-motion: reduce)").matches;

    if (!trigger || reduced || !document.startViewTransition) return set(next);

    const box = trigger.getBoundingClientRect();
    const x = box.left + box.width / 2;
    const y = box.top + box.height / 2;
    const radius = Math.hypot(Math.max(x, innerWidth - x), Math.max(y, innerHeight - y));

    const transition = document.startViewTransition(() => set(next));
    transition.ready.then(() => {
      document.documentElement.animate(
        { clipPath: [`circle(0px at ${x}px ${y}px)`, `circle(${radius}px at ${x}px ${y}px)`] },
        { duration: 480, easing: "cubic-bezier(0.4, 0, 0.2, 1)", pseudoElement: "::view-transition-new(root)" }
      );
    }).catch(() => {});
  }

  window.pcTheme = { toggle, set, choice, resolved };

  media.addEventListener("change", () => {
    if (choice() === "system") apply();
  });

  document.addEventListener("click", (e) => {
    const trigger = e.target.closest('[data-pc-trigger="theme-toggle"]');
    if (trigger) toggle(trigger);
  });

  // another tab changed it
  window.addEventListener("storage", (e) => {
    if (e.key === KEY) apply();
  });

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", apply);
  else apply();
})();
