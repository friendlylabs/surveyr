/**
 * pc-password.js — show/hide toggle and strength meter for password inputs.
 * Markup contract:
 *   <div data-pc-pw-strength>
 *     <span class="pc-input-group">
 *       <input type="password" class="pc-input" data-pc-pw-input ...>
 *       <button type="button" class="pc-input-addon" data-pc-pw-toggle aria-label="Show password">SHOW</button>
 *     </span>
 *     <div class="pc-strength"><span data-pc-strength-bar></span><span data-pc-strength-bar></span><span data-pc-strength-bar></span></div>
 *   </div>
 * The strength meter is opt-in: leave out data-pc-pw-strength and the bars.
 */
(function () {
  function passwordStrength(pw) {
    if (pw.length >= 12) return 3;
    if (pw.length >= 8) return 2;
    if (pw.length > 0) return 1;
    return 0;
  }

  function strengthColor(level) {
    return level === 1 ? "bg-pc-danger" : level === 2 ? "bg-pc-warning" : "bg-pc-success";
  }

  document.addEventListener("click", (e) => {
    const btn = e.target.closest("[data-pc-pw-toggle]");
    if (!btn) return;
    const wrapper = btn.closest(".pc-input-group") || btn.parentElement;
    const input = wrapper.querySelector("[data-pc-pw-input]");
    if (!input) return;
    const show = input.type === "password";
    input.type = show ? "text" : "password";
    btn.textContent = show ? "HIDE" : "SHOW";
    btn.setAttribute("aria-label", show ? "Hide password" : "Show password");
  });

  document.addEventListener("input", (e) => {
    const input = e.target.closest("[data-pc-pw-input]");
    if (!input) return;
    const container = input.closest("[data-pc-pw-strength]");
    if (!container) return;
    const bars = pc.qsa("[data-pc-strength-bar]", container);
    const level = passwordStrength(input.value);
    bars.forEach((bar, i) => {
      bar.classList.remove("bg-pc-danger", "bg-pc-warning", "bg-pc-success");
      if (i < level) bar.classList.add(strengthColor(level));
    });
  });
})();
