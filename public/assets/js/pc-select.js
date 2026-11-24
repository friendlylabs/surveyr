/**
 * pc-select.js — one select for everything: single or multiple, searchable, keyboard and touch friendly.
 * It enhances every native <select class="pc-select">; the native select stays in the form as the
 * source of truth, so posting, FormData, `select.value = ...`, form.reset() and fieldError() all keep working.
 *
 * Markup: a plain select (x-form.select renders it).
 *   <select class="pc-select" name="role_id">...</select>
 *   <select class="pc-select" name="campuses[]" multiple data-placeholder="Choose campuses">...</select>
 * Options:
 *   data-search="true|false"  search box on/off (default: on when there are more than 7 options)
 *   data-placeholder="..."    text when nothing is chosen (single selects also use an empty-value option's text)
 *   data-max-chips="3"        multiple: chips shown before collapsing into "+N more"
 *   data-pc-native            leave this select alone
 * The flyout opens below the field, or above it when there's more room there, and sizes itself to the space
 * available; on small screens (< 640px) it becomes a bottom sheet.
 * JS API: pcSelect.refresh(select) after replacing options without DOM mutation, pcSelect.close().
 * Events: native `change` (bubbling) on the select whenever the choice changes.
 */
(function () {
  const SEARCH_THRESHOLD = 7;
  const MAX_HEIGHT = 380;
  const GAP = 6;
  const EDGE = 8;
  const sheetQuery = window.matchMedia("(max-width: 639px)");
  const valueDesc = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, "value");
  const indexDesc = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, "selectedIndex");
  let uid = 0;
  let current = null; // the open instance

  class Select {
    constructor(select) {
      this.select = select;
      this.multiple = select.multiple;
      this.id = select.id || `pcSelect${++uid}`;
      select.id = this.id;
      select.dataset.pcEnhanced = "true";
      this.build();
      this.watch();
      this.renderOptions();
      this.renderValue();
    }

    /* ---------- structure ---------- */
    build() {
      const s = this.select;
      const label = s.id && document.querySelector(`label[for="${CSS.escape(s.id)}"]`);
      if (label && !label.id) label.id = `${this.id}Label`;
      this.labelText = label ? label.textContent.trim() : s.getAttribute("aria-label") || "";

      this.wrap = document.createElement("div");
      this.wrap.className = "pc-select-wrap";
      s.parentNode.insertBefore(this.wrap, s);
      this.wrap.appendChild(s);
      s.classList.add("pc-select-native");
      s.tabIndex = -1;
      s.setAttribute("aria-hidden", "true");

      this.trigger = el("div", "pc-select-trigger", {
        role: "combobox",
        tabindex: "0",
        "aria-haspopup": "listbox",
        "aria-expanded": "false",
        "aria-controls": `${this.id}Listbox`,
      });
      if (label) this.trigger.setAttribute("aria-labelledby", label.id);
      else if (this.labelText) this.trigger.setAttribute("aria-label", this.labelText);
      const describedBy = s.getAttribute("aria-describedby");
      if (describedBy) this.trigger.setAttribute("aria-describedby", describedBy);
      this.value = el("span", "pc-select-value");
      this.trigger.append(this.value, el("i", "ph ph-caret-down pc-select-caret", { "aria-hidden": "true" }));

      this.backdrop = el("div", "pc-select-backdrop");
      this.backdrop.hidden = true;

      this.flyout = el("div", "pc-select-flyout", { id: `${this.id}Flyout` });
      this.flyout.hidden = true;

      const head = el("div", "pc-select-sheet-head");
      const title = el("span", "truncate");
      title.textContent = this.labelText || "Choose";
      this.done = el("button", "pc-btn pc-btn-secondary pc-btn-sm", { type: "button" });
      this.done.textContent = "Done";
      head.append(title, this.done);

      this.searchBox = el("div", "pc-select-search");
      this.search = el("input", "pc-input", {
        type: "search",
        placeholder: "Search…",
        autocomplete: "off",
        "aria-label": `Search ${this.labelText}`.trim(),
        "aria-controls": `${this.id}Listbox`,
        "aria-autocomplete": "list",
      });
      this.searchBox.appendChild(this.search);

      this.actions = el("div", "pc-select-actions");
      this.count = el("span", "pc-select-count");
      this.allButton = el("button", "", { type: "button" });
      this.allButton.textContent = "Select all";
      this.clearButton = el("button", "", { type: "button" });
      this.clearButton.textContent = "Clear";
      this.actions.append(this.count, this.allButton, this.clearButton);
      this.actions.hidden = !this.multiple;

      this.list = el("ul", "pc-select-list", { id: `${this.id}Listbox`, role: "listbox" });
      if (this.multiple) this.list.setAttribute("aria-multiselectable", "true");
      if (label) this.list.setAttribute("aria-labelledby", label.id);

      this.empty = el("div", "pc-select-empty");
      this.empty.textContent = "No matches";
      this.empty.hidden = true;

      this.flyout.append(head, this.searchBox, this.actions, this.list, this.empty);
      this.wrap.append(this.trigger, this.backdrop, this.flyout);
    }

    /* ---------- keeping in step with the native select ---------- */
    watch() {
      const s = this.select;
      const self = this;
      // programmatic `select.value = x` / `selectedIndex = n` don't fire events, so the instance intercepts them
      Object.defineProperty(s, "value", {
        configurable: true,
        get() { return valueDesc.get.call(this); },
        set(v) { valueDesc.set.call(this, v); self.sync(); },
      });
      Object.defineProperty(s, "selectedIndex", {
        configurable: true,
        get() { return indexDesc.get.call(this); },
        set(v) { indexDesc.set.call(this, v); self.sync(); },
      });
      s.addEventListener("change", () => this.sync());
      // labels, data-pc-autofocus and fieldError focus the hidden select; hand focus to the visible field
      s.addEventListener("focus", () => this.trigger.focus());
      if (s.form) s.form.addEventListener("reset", () => setTimeout(() => this.sync()));
      new MutationObserver(() => this.refresh()).observe(s, { childList: true, subtree: true, attributes: true, attributeFilter: ["disabled", "label"] });

      this.trigger.addEventListener("click", (e) => {
        const chip = e.target.closest("[data-remove]");
        if (chip) {
          e.stopPropagation();
          this.toggle(this.select.options[Number(chip.dataset.remove)]);
          return;
        }
        this.isOpen() ? this.close() : this.open();
      });
      this.trigger.addEventListener("keydown", (e) => this.onKey(e, this.trigger));
      this.search.addEventListener("keydown", (e) => this.onKey(e, this.search));
      this.search.addEventListener("input", () => this.filter());
      this.flyout.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && e.target !== this.search) {
          e.stopPropagation();
          this.close(true);
        }
      });
      this.list.addEventListener("pointerdown", (e) => e.preventDefault()); // keep focus on trigger / search
      this.list.addEventListener("click", (e) => {
        const item = e.target.closest(".pc-select-option");
        if (item) this.choose(item);
      });
      this.list.addEventListener("pointermove", (e) => {
        const item = e.target.closest(".pc-select-option");
        if (item && !item.hasAttribute("data-active")) this.activate(item, false);
      });
      this.allButton.addEventListener("click", () => this.setAll(true));
      this.clearButton.addEventListener("click", () => this.setAll(false));
      this.done.addEventListener("click", () => this.close(true));
      this.backdrop.addEventListener("click", () => this.close(true));
    }

    sync() {
      this.renderValue();
      this.list.querySelectorAll(".pc-select-option").forEach((item) => {
        item.setAttribute("aria-selected", String(this.select.options[Number(item.dataset.index)]?.selected ?? false));
      });
      this.renderCount();
    }

    refresh() {
      this.renderOptions();
      this.renderValue();
      if (this.isOpen()) {
        this.filter();
      }
    }

    /* ---------- rendering ---------- */
    placeholder() {
      const empty = !this.multiple && Array.from(this.select.options).find((o) => o.value === "");
      return this.select.dataset.placeholder || (empty && empty.textContent.trim()) || (this.multiple ? "Choose…" : "Choose");
    }

    // the empty option is the "nothing chosen" state; it's offered in the list only when the field is optional
    listed(option) {
      if (option.value !== "") return true;
      return !this.multiple && !this.select.required;
    }

    renderOptions() {
      const s = this.select;
      this.list.textContent = "";
      let group = null;
      Array.from(s.options).forEach((option, index) => {
        if (!this.listed(option)) return;
        const parent = option.parentElement.tagName === "OPTGROUP" ? option.parentElement : null;
        if (parent !== group) {
          group = parent;
          if (group) {
            const header = el("li", "pc-select-group", { role: "presentation" });
            header.textContent = group.label;
            this.list.appendChild(header);
          }
        }
        const item = el("li", "pc-select-option", {
          id: `${this.id}Option${index}`,
          role: "option",
          "aria-selected": String(option.selected),
        });
        item.dataset.index = index;
        if (option.disabled || (parent && parent.disabled)) item.setAttribute("aria-disabled", "true");
        if (this.multiple) item.appendChild(el("span", "pc-select-box", { "aria-hidden": "true" }));
        const text = el("span", "pc-select-option-text");
        text.textContent = option.textContent.trim();
        if (option.value === "") text.classList.add("text-pc-muted");
        item.appendChild(text);
        this.list.appendChild(item);
      });

      const searchable = s.dataset.search ? s.dataset.search === "true" : this.list.querySelectorAll(".pc-select-option").length > SEARCH_THRESHOLD;
      this.searchBox.hidden = !searchable;

      const disabled = s.disabled;
      this.trigger.setAttribute("aria-disabled", String(disabled));
      this.trigger.tabIndex = disabled ? -1 : 0;
      if (disabled) this.close();
      this.renderCount();
    }

    renderValue() {
      const chosen = Array.from(this.select.selectedOptions).filter((o) => o.value !== "");
      this.value.textContent = "";
      if (!chosen.length) {
        const ph = el("span", "pc-select-placeholder");
        ph.textContent = this.placeholder();
        this.value.appendChild(ph);
        return;
      }
      if (!this.multiple) {
        const text = el("span", "pc-select-text");
        text.textContent = chosen[0].textContent.trim();
        this.value.appendChild(text);
        return;
      }
      const max = Math.max(1, Number(this.select.dataset.maxChips) || 3);
      chosen.slice(0, max).forEach((option) => {
        const chip = el("span", "pc-select-chip");
        const label = el("span", "pc-select-chip-label");
        label.textContent = option.textContent.trim();
        const x = el("span", "pc-select-chip-x", { "aria-hidden": "true", title: "Remove" });
        x.dataset.remove = option.index;
        x.textContent = "×";
        chip.append(label, x);
        this.value.appendChild(chip);
      });
      if (chosen.length > max) {
        const more = el("span", "pc-select-more");
        more.textContent = `+${chosen.length - max} more`;
        this.value.appendChild(more);
      }
    }

    renderCount() {
      if (!this.multiple) return;
      const total = Array.from(this.select.options).filter((o) => o.value !== "").length;
      const chosen = Array.from(this.select.selectedOptions).filter((o) => o.value !== "").length;
      this.count.textContent = `${chosen} of ${total} selected`;
    }

    /* ---------- choosing ---------- */
    items(visibleOnly = true) {
      return Array.from(this.list.querySelectorAll(".pc-select-option")).filter((i) => !visibleOnly || !i.hidden);
    }

    choose(item) {
      if (!item || item.getAttribute("aria-disabled") === "true") return;
      const option = this.select.options[Number(item.dataset.index)];
      if (this.multiple) {
        this.toggle(option);
        this.activate(item, false);
      } else {
        if (!option.selected) {
          option.selected = true;
          this.changed();
        }
        this.close(true);
      }
    }

    toggle(option) {
      if (!option || option.disabled) return;
      option.selected = !option.selected;
      this.changed();
    }

    // select all / clear apply to the options showing under the current search
    setAll(on) {
      this.items().forEach((item) => {
        const option = this.select.options[Number(item.dataset.index)];
        if (!option.disabled && item.getAttribute("aria-disabled") !== "true") option.selected = on;
      });
      this.changed();
    }

    changed() {
      this.select.dispatchEvent(new Event("input", { bubbles: true }));
      this.select.dispatchEvent(new Event("change", { bubbles: true }));
    }

    /* ---------- search & active option ---------- */
    filter() {
      const q = this.search.value.trim().toLowerCase();
      let shown = 0;
      this.list.querySelectorAll(".pc-select-option").forEach((item) => {
        const match = !q || item.textContent.toLowerCase().includes(q);
        item.hidden = !match;
        if (match) shown++;
      });
      this.list.querySelectorAll(".pc-select-group").forEach((header) => {
        let next = header.nextElementSibling;
        let any = false;
        while (next && !next.classList.contains("pc-select-group")) {
          if (!next.hidden) any = true;
          next = next.nextElementSibling;
        }
        header.hidden = !any;
      });
      this.empty.hidden = shown > 0;
      const active = this.list.querySelector("[data-active]");
      if (!active || active.hidden) this.activate(this.items()[0], true);
      if (this.isOpen()) this.place();
    }

    activate(item, scroll = true) {
      this.list.querySelectorAll("[data-active]").forEach((i) => i.removeAttribute("data-active"));
      const owner = this.searchBox.hidden ? this.trigger : this.search;
      [this.trigger, this.search].forEach((n) => n.removeAttribute("aria-activedescendant"));
      if (!item) return;
      item.setAttribute("data-active", "");
      owner.setAttribute("aria-activedescendant", item.id);
      if (scroll) item.scrollIntoView({ block: "nearest" });
    }

    move(step) {
      const list = this.items().filter((i) => i.getAttribute("aria-disabled") !== "true");
      if (!list.length) return;
      const last = list.length - 1;
      const i = list.indexOf(this.list.querySelector("[data-active]"));
      let next;
      if (step === Infinity) next = last;
      else if (step === -Infinity) next = 0;
      else if (i === -1) next = step > 0 ? 0 : last;
      else next = Math.min(last, Math.max(0, i + step));
      this.activate(list[next]);
    }

    onKey(e, from) {
      if (this.select.disabled) return;
      const open = this.isOpen();
      switch (e.key) {
        case "ArrowDown":
        case "ArrowUp":
          e.preventDefault();
          if (!open) this.open();
          else this.move(e.key === "ArrowDown" ? 1 : -1);
          break;
        case "Home":
        case "End":
          if (open && from === this.trigger) {
            e.preventDefault();
            this.move(e.key === "End" ? Infinity : -Infinity);
          }
          break;
        case "Enter":
          e.preventDefault();
          if (open) this.choose(this.list.querySelector("[data-active]"));
          else this.open();
          break;
        case " ":
          if (from === this.search) break;
          e.preventDefault();
          if (open) this.choose(this.list.querySelector("[data-active]"));
          else this.open();
          break;
        case "Escape":
          if (open) {
            e.preventDefault();
            e.stopPropagation(); // don't also close the modal or panel around the field
            this.close(true);
          }
          break;
        case "Tab":
          if (open) this.close();
          break;
        default:
          // no search box: typing jumps to the next option starting with that letter
          if (open && from === this.trigger && e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
            const key = e.key.toLowerCase();
            const list = this.items();
            const start = list.indexOf(this.list.querySelector("[data-active]")) + 1;
            const hit = [...list.slice(start), ...list.slice(0, start)].find((i) => i.textContent.trim().toLowerCase().startsWith(key));
            if (hit) this.activate(hit);
          }
      }
    }

    /* ---------- open / close / place ---------- */
    isOpen() {
      return current === this;
    }

    open() {
      if (this.isOpen() || this.select.disabled) return;
      if (current) current.close();
      current = this;
      this.sheet = sheetQuery.matches;
      this.flyout.dataset.mode = this.sheet ? "sheet" : "pop";
      this.flyout.hidden = false;
      this.backdrop.hidden = !this.sheet;
      this.trigger.setAttribute("aria-expanded", "true");
      this.search.value = "";
      this.filter();
      this.activate(this.list.querySelector('.pc-select-option[aria-selected="true"]:not([hidden])') || this.items()[0]);
      this.place();
      if (this.sheet) pc.lockScroll(true);
      if (!this.searchBox.hidden && !this.sheet) this.search.focus(); // no keyboard pop-up on phones until asked

      this.unOutside = pc.onOutsideClick(this.wrap, () => this.close());
      this.reposition = (e) => {
        // the list scrolling itself isn't a reason to re-place; doing so would measure it unclamped and reset its scroll
        if (e && e.type === "scroll" && this.flyout.contains(e.target)) return;
        const r = this.trigger.getBoundingClientRect();
        if (!this.sheet && (r.bottom < 0 || r.top > window.innerHeight)) this.close();
        else this.place();
      };
      window.addEventListener("scroll", this.reposition, true);
      window.addEventListener("resize", this.reposition);
    }

    close(refocus = false) {
      if (!this.isOpen()) return;
      current = null;
      this.flyout.hidden = true;
      this.backdrop.hidden = true;
      this.trigger.setAttribute("aria-expanded", "false");
      this.activate(null);
      if (this.sheet) pc.lockScroll(false);
      this.unOutside && this.unOutside();
      window.removeEventListener("scroll", this.reposition, true);
      window.removeEventListener("resize", this.reposition);
      if (refocus) this.trigger.focus();
    }

    // below the field when the list fits or there's more room below, otherwise above; never off-screen
    place() {
      const f = this.flyout.style;
      if (this.sheet !== sheetQuery.matches) {
        // crossed the phone breakpoint while open (rotation, resize): switch layouts
        this.sheet = sheetQuery.matches;
        this.flyout.dataset.mode = this.sheet ? "sheet" : "pop";
        this.backdrop.hidden = !this.sheet;
        pc.lockScroll(this.sheet);
      }
      if (this.sheet) {
        f.top = f.left = f.width = f.maxHeight = "";
        return;
      }
      const r = this.trigger.getBoundingClientRect();
      const vw = window.innerWidth;
      const vh = window.innerHeight;
      const width = Math.min(Math.max(r.width, 240), vw - EDGE * 2);
      f.width = width + "px";
      f.left = Math.min(Math.max(EDGE, r.left), vw - width - EDGE) + "px";
      f.maxHeight = "none";
      const natural = Math.min(this.flyout.offsetHeight, MAX_HEIGHT);
      const below = vh - r.bottom - GAP - EDGE;
      const above = r.top - GAP - EDGE;
      const down = below >= natural || below >= above;
      const height = Math.min(natural, down ? below : above);
      f.maxHeight = Math.max(height, 120) + "px";
      f.top = (down ? r.bottom + GAP : Math.max(EDGE, r.top - GAP - height)) + "px";
      this.flyout.dataset.side = down ? "bottom" : "top";
    }
  }

  function el(tag, className, attrs = {}) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    Object.entries(attrs).forEach(([k, v]) => node.setAttribute(k, v));
    return node;
  }

  const instances = new WeakMap();

  function enhance(root) {
    const scope = root && root.querySelectorAll ? root : document;
    const found = scope.matches && scope.matches("select.pc-select") ? [scope] : [];
    found.concat(Array.from(scope.querySelectorAll("select.pc-select"))).forEach((s) => {
      if (instances.has(s) || s.hasAttribute("data-pc-native")) return;
      instances.set(s, new Select(s));
    });
  }

  window.pcSelect = {
    enhance,
    refresh: (select) => instances.get(select)?.refresh(),
    close: () => current && current.close(),
  };

  function start() {
    enhance(document);
    // selects added later (page scripts, fetched fragments) are picked up as they arrive
    new MutationObserver((records) => {
      records.forEach((r) => r.addedNodes.forEach((n) => n.nodeType === 1 && enhance(n)));
    }).observe(document.body, { childList: true, subtree: true });
  }

  document.readyState === "loading" ? document.addEventListener("DOMContentLoaded", start) : start();
})();
