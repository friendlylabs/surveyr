/**
 * pc-toggle.js — toggle switches.
 * Markup contract:
 *   <button type="button" role="switch" aria-checked="false" data-pc-trigger="toggle" data-pc-target="#showHints" data-pc-on="false" class="pc-toggle">
 *     <span class="pc-toggle-thumb"></span>
 *   </button>
 *   <input type="checkbox" id="showHints" name="show_hints" hidden data-pc-toggle-input>
 * The paired hidden native checkbox (matched via data-pc-target -> #id, or a
 * sibling [data-pc-toggle-input] when no target is given) is kept in sync so
 * the value still submits with a form; visuals are driven purely by
 * data-pc-on, following the same attribute contract as every other pc-*
 * component (no CSS class toggling).
 * JS API: pcToggle.set(triggerEl, on) / toggle(triggerEl)
 * Events: `pc:toggle` (detail: { on }) on the trigger.
 */
(function () {
  function getInput(trigger) {
    const targetSel = trigger.getAttribute("data-pc-target");
    if (targetSel) return document.querySelector(targetSel);
    return trigger.querySelector("[data-pc-toggle-input]") || trigger.parentElement.querySelector("[data-pc-toggle-input]");
  }

  function set(trigger, on) {
    trigger.setAttribute("data-pc-on", on ? "true" : "false");
    trigger.setAttribute("aria-checked", on ? "true" : "false");
    const input = getInput(trigger);
    if (input) {
      input.checked = on;
      input.dispatchEvent(new Event("change", { bubbles: true }));
    }
    trigger.dispatchEvent(new CustomEvent("pc:toggle", { detail: { on }, bubbles: true }));
  }

  function toggle(trigger) {
    set(trigger, trigger.getAttribute("data-pc-on") !== "true");
  }

  window.pcToggle = { set, toggle };

  document.addEventListener("click", (e) => {
    const trigger = e.target.closest('[data-pc-trigger="toggle"]');
    if (trigger && !trigger.disabled) toggle(trigger);
  });
})();
