/**
 * pc-date.js — one picker for dates and times: a date, a time or both, single or a range; keyboard and touch friendly.
 * It enhances every .pc-date field (x-form.date renders it). The inputs inside stay in the form as the source of truth
 * and hold plain values — Y-m-d, H:i or Y-m-d H:i, in the display zone — so posting, FormData,
 * `input.value = ...`, form.reset(), fillForm() and fieldError() all keep working.
 *
 * Markup:
 *   <div class="pc-date" data-pc-date="date|time|datetime" data-range data-display="j M Y" data-zone="Africa/Nairobi">
 *     <input name="from" value="2026-10-12">
 *     <input name="to" value="2026-10-18">        (ranges only)
 *   </div>
 * Options (on the field):
 *   data-display="j M Y"        how the value is shown: PHP date() characters, the same formats as App\Support\DateFormat
 *   data-min / data-max         earliest / latest allowed value, in the posted shape
 *   data-step="5"               minutes between the times offered
 *   data-presets='{"Last 7 days": ["2026-10-06", "2026-10-12"]}'   date range shortcuts
 *   data-zone="Africa/Nairobi"  the zone values are in, so Today and Now are the app's rather than the browser's
 *   data-week-start="1"         first day of the week, 0 (Sunday) to 6 (default Monday)
 *   data-placeholder="..."      text shown with no value
 * Picking a single date closes the picker. With a time, or for a range, it stays open until Done, a click outside or
 * tabbing out of it, which keep the edit; Escape drops it. On small screens (< 640px) it becomes a bottom sheet.
 * JS API: pcDate.enhance(root), pcDate.set(field, start, end), pcDate.close().
 * Events: `input` on each input that changed, then one `change` (bubbling) on the first input.
 */
(function () {
  const GAP = 6;
  const EDGE = 8;
  const sheetQuery = window.matchMedia("(max-width: 639px)");
  const valueDesc = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, "value");
  const TIME_CHARS = /[GHghisAauvT]/; // keep in step with DateFormat::TIME_CHARACTERS
  const DISPLAY = { date: "j M Y", time: "H:i", datetime: "j M Y, H:i" };
  let uid = 0;
  let current = null; // the open instance

  /* ---------- calendar arithmetic: a day is a local noon, so daylight saving never moves it ---------- */
  const pad = (n) => String(n).padStart(2, "0");
  const ymd = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
  const hm = (minutes) => `${pad(Math.floor(minutes / 60))}:${pad(minutes % 60)}`;
  const day = (y, m, d) => new Date(y, m, d, 12);
  const addDays = (d, n) => day(d.getFullYear(), d.getMonth(), d.getDate() + n);
  const dayNumber = (d) => d.getFullYear() * 10000 + (d.getMonth() + 1) * 100 + d.getDate();
  const sameDay = (a, b) => !!(a && b) && dayNumber(a) === dayNumber(b);

  function addMonths(d, n) {
    const first = day(d.getFullYear(), d.getMonth() + n, 1);
    const last = day(first.getFullYear(), first.getMonth() + 1, 0).getDate();
    return day(first.getFullYear(), first.getMonth(), Math.min(d.getDate(), last));
  }

  function parseDate(s) {
    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s || "");
    if (!m) return null;
    const d = day(+m[1], +m[2] - 1, +m[3]);
    return d.getMonth() === +m[2] - 1 ? d : null;
  }

  /** Minutes since midnight. */
  function parseTime(s) {
    const m = /^(\d{1,2}):(\d{2})$/.exec(s || "");
    return m && +m[1] < 24 && +m[2] < 60 ? +m[1] * 60 + +m[2] : null;
  }

  /* ---------- values: a point is { date: Date | null, time: minutes | null } ---------- */
  function parseValue(mode, s) {
    s = String(s || "").trim();
    if (mode === "date") {
      const date = parseDate(s);
      return date ? { date, time: null } : null;
    }
    if (mode === "time") {
      const time = parseTime(s.slice(0, 5));
      return time === null ? null : { date: null, time };
    }
    const [d, t] = s.split(/[ T]/);
    const date = parseDate(d);
    const time = parseTime((t || "").slice(0, 5));
    return date && time !== null ? { date, time } : null;
  }

  function serialize(mode, p) {
    if (!p) return "";
    if (mode === "date") return ymd(p.date);
    if (mode === "time") return hm(p.time);
    return `${ymd(p.date)} ${hm(p.time)}`;
  }

  // a number that orders points (and compares them with min / max)
  const order = (p) => (p.date ? dayNumber(p.date) * 1440 : 0) + (p.time ?? 0);

  // now on the field's clock (data-zone)
  function zonedNow(zone) {
    try {
      const parts = Object.fromEntries(
        new Intl.DateTimeFormat("en-CA", { timeZone: zone || undefined, year: "numeric", month: "2-digit", day: "2-digit", hour: "2-digit", minute: "2-digit", hourCycle: "h23" })
          .formatToParts(new Date())
          .map((part) => [part.type, part.value])
      );
      return { date: day(+parts.year, +parts.month - 1, +parts.day), time: +parts.hour * 60 + +parts.minute };
    } catch {
      const d = new Date();
      return { date: day(d.getFullYear(), d.getMonth(), d.getDate()), time: d.getHours() * 60 + d.getMinutes() };
    }
  }

  /* ---------- formatting: the PHP date() characters DateFormat uses, so both sides print alike ---------- */
  const nameFormats = {};
  function name(locale, kind, d) {
    const options = { M: { month: "short" }, F: { month: "long" }, D: { weekday: "short" }, l: { weekday: "long" } }[kind];
    const cacheKey = `${locale}|${kind}`;
    nameFormats[cacheKey] = nameFormats[cacheKey] || new Intl.DateTimeFormat(locale, options);
    return nameFormats[cacheKey].format(d);
  }

  function format(p, pattern, locale) {
    if (!p) return "";
    const d = p.date;
    const t = p.time ?? 0;
    const h = Math.floor(t / 60);
    let out = "";
    for (let i = 0; i < pattern.length; i++) {
      const c = pattern[i];
      if (c === "\\") {
        out += pattern[++i] ?? "";
        continue;
      }
      if (!d && "djDlMFmnYy".includes(c)) continue;
      switch (c) {
        case "d": out += pad(d.getDate()); break;
        case "j": out += d.getDate(); break;
        case "D": case "l": case "M": case "F": out += name(locale, c, d); break;
        case "m": out += pad(d.getMonth() + 1); break;
        case "n": out += d.getMonth() + 1; break;
        case "Y": out += d.getFullYear(); break;
        case "y": out += String(d.getFullYear()).slice(-2); break;
        case "H": out += pad(h); break;
        case "G": out += h; break;
        case "h": out += pad(h % 12 || 12); break;
        case "g": out += h % 12 || 12; break;
        case "i": out += pad(t % 60); break;
        case "s": out += "00"; break;
        case "A": out += h < 12 ? "AM" : "PM"; break;
        case "a": out += h < 12 ? "am" : "pm"; break;
        case "T": case "u": case "v": break;
        default: out += c;
      }
    }
    return out.trim();
  }

  // mirrors DateFormat::range(): 12 Oct 2026, 09:00 – 17:30 · 12 – 18 Oct 2026 · 28 Sep – 4 Oct 2026
  function formatRange(a, b, pattern, locale, sep = " – ") {
    if (!a || !b) return a ? pc.t("From :date", { date: format(a, pattern, locale) }) : b ? pc.t("Until :date", { date: format(b, pattern, locale) }) : "";
    const full = format(b, pattern, locale);
    if (format(a, pattern, locale) === full) return full;
    const timeAt = pattern.search(TIME_CHARS);
    if (timeAt > 0 && sameDay(a.date, b.date)) return format(a, pattern, locale) + sep + format(b, pattern.slice(timeAt), locale);
    if (timeAt === -1 && a.date && b.date) {
      const sameYear = a.date.getFullYear() === b.date.getFullYear();
      const dayFirst = /^([dj])\W/.exec(pattern);
      if (sameYear && a.date.getMonth() === b.date.getMonth() && dayFirst) return format(a, dayFirst[1], locale) + sep + full;
      if (sameYear && /[\s,]*Y$/.test(pattern)) return format(a, pattern.replace(/[\s,]*Y$/, ""), locale) + sep + full;
    }
    return format(a, pattern, locale) + sep + full;
  }

  class DatePicker {
    constructor(root) {
      this.root = root;
      this.mode = DISPLAY[root.dataset.pcDate] ? root.dataset.pcDate : "date";
      this.range = root.hasAttribute("data-range");
      this.hasDate = this.mode !== "time";
      this.hasTime = this.mode !== "date";
      this.inputs = Array.from(root.querySelectorAll(":scope > input")).slice(0, this.range ? 2 : 1);
      this.locale = document.documentElement.lang || "en";
      this.id = this.inputs[0].id || `pcDate${++uid}`;
      this.inputs[0].id = this.id;
      root.dataset.pcEnhanced = "true";
      this.build();
      this.watch();
      this.renderValue();
    }

    /* ---------- options ---------- */
    get pattern() {
      return this.root.dataset.display || DISPLAY[this.mode];
    }

    get step() {
      return Math.min(60, Math.max(1, Number(this.root.dataset.step) || 5));
    }

    get weekStart() {
      const start = parseInt(this.root.dataset.weekStart, 10);
      return start >= 0 && start <= 6 ? start : 1;
    }

    get twelveHour() {
      return /[ghAa]/.test(this.pattern.replace(/\\./g, ""));
    }

    bound(which) {
      return parseValue(this.mode, this.root.dataset[which]);
    }

    presets() {
      try {
        return Object.entries(JSON.parse(this.root.dataset.presets || "{}"));
      } catch {
        return [];
      }
    }

    values() {
      return this.inputs.map((input) => parseValue(this.mode, valueDesc.get.call(input)));
    }

    now() {
      return zonedNow(this.root.dataset.zone);
    }

    /* ---------- structure ---------- */
    build() {
      const label = document.querySelector(`label[for="${CSS.escape(this.id)}"]`);
      if (label && !label.id) label.id = `${this.id}Label`;
      this.labelText = label ? label.textContent.trim() : this.inputs[0].getAttribute("aria-label") || "";
      this.inputs.forEach((input) => {
        input.classList.add("pc-date-native");
        input.tabIndex = -1;
        input.setAttribute("aria-hidden", "true");
      });

      this.trigger = el("div", "pc-date-trigger", {
        role: "button",
        tabindex: "0",
        "aria-haspopup": "dialog",
        "aria-expanded": "false",
        "aria-controls": `${this.id}Picker`,
      });
      this.value = el("span", "pc-date-value", { id: `${this.id}Value` });
      if (label) this.trigger.setAttribute("aria-labelledby", `${label.id} ${this.id}Value`);
      else if (this.labelText) this.trigger.setAttribute("aria-label", this.labelText);
      const describedBy = this.inputs[0].getAttribute("aria-describedby");
      if (describedBy) this.trigger.setAttribute("aria-describedby", describedBy);
      this.clearX = el("span", "pc-date-clear", { "aria-hidden": "true", title: pc.t("Clear") });
      this.clearX.textContent = "×";
      this.trigger.append(el("i", `fa-regular fa-${this.hasDate ? "calendar" : "clock"} pc-date-icon`, { "aria-hidden": "true" }), this.value, this.clearX);

      this.backdrop = el("div", "pc-date-backdrop");
      this.backdrop.hidden = true;

      this.flyout = el("div", "pc-date-flyout", { id: `${this.id}Picker`, role: "dialog", "aria-label": this.labelText || pc.t("Choose") });
      this.flyout.hidden = true;

      const head = el("div", "pc-date-sheet-head");
      const title = el("span", "truncate");
      title.textContent = this.labelText || pc.t("Choose");
      this.headDone = el("button", "pc-btn pc-btn-secondary pc-btn-sm", { type: "button" });
      this.headDone.textContent = pc.t("Done");
      head.append(title, this.headDone);

      const body = el("div", "pc-date-body");
      this.presetList = el("div", "pc-date-presets", { role: "group", "aria-label": pc.t("Shortcuts") });
      this.calendar = el("div", "pc-date-calendar");
      this.clock = el("div", "pc-date-clock");
      body.append(this.presetList, this.calendar, this.clock);
      this.presetList.hidden = !this.range || !this.hasDate;
      this.calendar.hidden = !this.hasDate;
      this.clock.hidden = !this.hasTime;

      const foot = el("div", "pc-date-foot");
      this.nowButton = el("button", "pc-btn pc-btn-ghost pc-btn-sm", { type: "button" });
      this.nowButton.textContent = this.mode === "date" ? pc.t("Today") : pc.t("Now");
      this.nowButton.hidden = this.range;
      this.summary = el("span", "pc-date-summary", { "aria-live": "polite" });
      this.clearButton = el("button", "pc-btn pc-btn-ghost pc-btn-sm", { type: "button" });
      this.clearButton.textContent = pc.t("Clear");
      this.doneButton = el("button", "pc-btn pc-btn-primary pc-btn-sm", { type: "button" });
      this.doneButton.textContent = pc.t("Done");
      this.doneButton.hidden = !this.hasTime && !this.range;
      foot.append(this.nowButton, this.summary, this.clearButton, this.doneButton);

      this.flyout.append(head, body, foot);
      this.root.append(this.trigger, this.backdrop, this.flyout);
    }

    /* ---------- keeping in step with the inputs ---------- */
    watch() {
      const self = this;
      this.inputs.forEach((input) => {
        // programmatic `input.value = x` (fillForm) fires no event, so the instance intercepts it
        Object.defineProperty(input, "value", {
          configurable: true,
          get() { return valueDesc.get.call(this); },
          set(v) { valueDesc.set.call(this, v); self.renderValue(); },
        });
        // labels, data-pc-autofocus and fieldError focus the hidden input; hand focus to the visible field
        input.addEventListener("focus", () => this.trigger.focus());
      });
      const form = this.inputs[0].form;
      if (form) form.addEventListener("reset", () => setTimeout(() => this.renderValue()));
      const refresh = () => {
        this.renderValue();
        if (this.isOpen()) this.render();
      };
      this.inputs.forEach((input) => new MutationObserver(refresh).observe(input, { attributes: true, attributeFilter: ["disabled", "required"] }));
      new MutationObserver(refresh).observe(this.root, { attributes: true, attributeFilter: ["data-min", "data-max", "data-display", "data-presets"] });

      this.trigger.addEventListener("click", (e) => {
        if (e.target === this.clearX) {
          e.stopPropagation();
          this.clear();
          return;
        }
        this.isOpen() ? this.close(false, true) : this.open();
      });
      this.trigger.addEventListener("keydown", (e) => {
        if (this.disabled()) return;
        if (["Enter", " ", "ArrowDown"].includes(e.key)) {
          e.preventDefault();
          this.open();
        } else if (e.key === "Escape" && this.isOpen()) {
          e.preventDefault();
          e.stopPropagation();
          this.close();
        } else if ((e.key === "Backspace" || e.key === "Delete") && !this.clearX.hidden) {
          e.preventDefault();
          this.clear();
        }
      });

      this.flyout.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
          e.preventDefault();
          e.stopPropagation(); // don't also close the modal or panel around the field
          this.close(true);
        }
      });
      // tabbing out of the picker keeps the edit (a re-render drops focus to nothing; that isn't leaving)
      this.root.addEventListener("focusout", (e) => {
        if (this.isOpen() && e.relatedTarget && !this.root.contains(e.relatedTarget)) this.close(false, true);
      });
      this.backdrop.addEventListener("click", () => this.close(true, true));
      this.headDone.addEventListener("click", () => this.close(true, true));
      this.doneButton.addEventListener("click", () => this.close(true, true));
      this.clearButton.addEventListener("click", () => {
        this.clear();
        this.close(true);
      });
      this.nowButton.addEventListener("click", () => this.pickNow());

      this.presetList.addEventListener("click", (e) => {
        const button = e.target.closest("[data-preset]");
        if (button) this.pickPreset(JSON.parse(button.dataset.preset));
      });

      this.calendar.addEventListener("click", (e) => {
        const button = e.target.closest("button");
        if (!button || button.disabled) return;
        if (button.dataset.date) this.pickDay(parseDate(button.dataset.date));
        else if (button.dataset.nav) this.navigate(button.dataset.nav);
        else if (button.dataset.month) this.showMonth(Number(button.dataset.month));
        else if (button.dataset.year) this.showYear(Number(button.dataset.year));
      });
      this.calendar.addEventListener("keydown", (e) => this.onCalendarKey(e));
      // while picking a range's end, the span follows the pointer
      this.calendar.addEventListener("pointerover", (e) => {
        const button = e.target.closest(".pc-date-day");
        if (!button || !this.range || this.picking !== 1 || button.disabled) return;
        this.hover = parseDate(button.dataset.date);
        this.markDays();
      });
      this.calendar.addEventListener("pointerleave", () => {
        if (!this.hover) return;
        this.hover = null;
        this.markDays();
      });

      this.clock.addEventListener("click", (e) => {
        const button = e.target.closest("button");
        if (!button || button.disabled) return;
        if (button.dataset.end) {
          this.clockEnd = Number(button.dataset.end);
          this.renderClock();
        } else if (button.dataset.hour) this.setTime(Number(button.dataset.hour) * 60 + (this.clockPoint()?.time ?? this.defaultTime(this.clockEnd)) % 60);
        else if (button.dataset.minute) this.setTime(Math.floor((this.clockPoint()?.time ?? this.defaultTime(this.clockEnd)) / 60) * 60 + Number(button.dataset.minute), true);
      });
    }

    disabled() {
      return this.inputs[0].disabled;
    }

    /* ---------- the field ---------- */
    renderValue() {
      const [a, b] = this.values();
      const text = this.range ? formatRange(a, b, this.pattern, this.locale) : format(a, this.pattern, this.locale);
      this.value.textContent = "";
      const span = el("span", text ? "pc-date-text" : "pc-date-placeholder");
      span.textContent = text || this.root.dataset.placeholder || pc.t("Choose");
      this.value.appendChild(span);
      const disabled = this.disabled();
      this.clearX.hidden = !text || this.inputs[0].required || disabled;
      this.clearButton.hidden = this.inputs[0].required;
      this.trigger.setAttribute("aria-disabled", String(disabled));
      this.trigger.tabIndex = disabled ? -1 : 0;
      if (disabled) this.close();
    }

    write(values) {
      let changed = false;
      this.inputs.forEach((input, i) => {
        const value = values[i] ?? "";
        if (valueDesc.get.call(input) === value) return;
        valueDesc.set.call(input, value);
        input.dispatchEvent(new Event("input", { bubbles: true }));
        changed = true;
      });
      this.renderValue();
      if (changed) this.inputs[0].dispatchEvent(new Event("change", { bubbles: true }));
    }

    clear() {
      this.write(this.inputs.map(() => ""));
    }

    /* ---------- the edit in progress ---------- */
    startDraft() {
      const [a, b] = this.values();
      this.draft = [a, this.range ? b : null];
      this.picking = this.range && a && !b ? 1 : 0; // which end the next day picked sets
      this.clockEnd = 0; // which end the time columns edit
      this.hover = null;
      const focus = a?.date || this.clampDay(this.now().date);
      this.cursor = focus;
      this.view = day(focus.getFullYear(), focus.getMonth(), 1);
      this.viewMode = "days";
    }

    complete(p) {
      return !!p && (!this.hasDate || !!p.date) && (!this.hasTime || p.time !== null);
    }

    draftComplete() {
      return this.range ? this.complete(this.draft[0]) && this.complete(this.draft[1]) : this.complete(this.draft[0]);
    }

    commit() {
      let [a, b] = this.draft;
      // a time-only range may run past midnight (22:00 – 02:00); dated ones are put in order
      if (this.range && this.hasDate && order(b) < order(a)) [a, b] = [b, a];
      this.write([serialize(this.mode, a), serialize(this.mode, b)]);
    }

    /** The time picked for an end that has none yet: now for a single value, the whole day for a range. */
    defaultTime(end) {
      if (!this.hasTime) return null;
      if (this.range) return end === 0 ? 0 : 23 * 60 + 59;
      const t = Math.ceil(this.now().time / this.step) * this.step;
      return Math.min(t, 24 * 60 - this.step);
    }

    clampDay(d) {
      const min = this.bound("min");
      const max = this.bound("max");
      if (min?.date && dayNumber(d) < dayNumber(min.date)) return min.date;
      if (max?.date && dayNumber(d) > dayNumber(max.date)) return max.date;
      return d;
    }

    /** Whether any day from first to last is inside min / max. */
    spanAllowed(first, last) {
      const min = this.bound("min");
      const max = this.bound("max");
      return !(min?.date && dayNumber(last) < dayNumber(min.date)) && !(max?.date && dayNumber(first) > dayNumber(max.date));
    }

    dayAllowed(d) {
      return this.spanAllowed(d, d);
    }

    /** Whether any minute from..to on the given date (null for a time-only field) is inside min / max. */
    timeAllowed(date, from, to) {
      const min = this.bound("min");
      const max = this.bound("max");
      if (this.hasDate && !date) return true;
      if (min && order({ date, time: to }) < order(min)) return false;
      if (max && order({ date, time: from }) > order(max)) return false;
      return true;
    }

    pickDay(d) {
      if (!d || !this.dayAllowed(d)) return;
      this.cursor = d;
      this.hover = null;
      const [a, b] = this.draft;
      if (!this.range) {
        this.draft = [{ date: d, time: a?.time ?? this.defaultTime(0) }, null];
        if (!this.hasTime) return this.finish();
      } else if (this.picking === 0 || !a) {
        this.draft = [{ date: d, time: a?.time ?? this.defaultTime(0) }, null];
        this.picking = 1;
        this.clockEnd = 0;
      } else {
        const endTime = b?.time ?? this.defaultTime(1);
        // picked before the start: that day becomes the start, the old start the end; times stay with their ends
        this.draft = dayNumber(d) < dayNumber(a.date) ? [{ date: d, time: a.time }, { date: a.date, time: endTime }] : [a, { date: d, time: endTime }];
        this.picking = 0;
        this.clockEnd = 1;
        if (!this.hasTime) return this.finish();
      }
      if (this.cursor.getMonth() !== this.view.getMonth() || this.cursor.getFullYear() !== this.view.getFullYear()) {
        this.view = day(d.getFullYear(), d.getMonth(), 1);
      }
      this.render();
      this.focusCursor();
    }

    pickPreset([first, last]) {
      const a = parseDate(first);
      const b = parseDate(last);
      if (!a || !b) return;
      this.draft = [{ date: a, time: this.defaultTime(0) }, { date: b, time: this.defaultTime(1) }];
      this.picking = 0;
      if (!this.hasTime) return this.finish();
      this.cursor = b;
      this.view = day(b.getFullYear(), b.getMonth(), 1);
      this.viewMode = "days";
      this.render();
    }

    pickNow() {
      const now = this.now();
      if (this.mode === "date") return this.pickDay(now.date);
      this.draft = [{ date: this.hasDate ? now.date : null, time: now.time }, null];
      this.cursor = now.date;
      this.view = day(now.date.getFullYear(), now.date.getMonth(), 1);
      this.render();
    }

    clockPoint() {
      return this.draft[this.clockEnd];
    }

    /** Advance: a minute was picked, so a range's start time is done and the columns move on to the end's. */
    setTime(minutes, advance = false) {
      const i = this.clockEnd;
      const p = this.draft[i];
      const date = p?.date ?? (this.hasDate ? this.draft[0]?.date ?? this.cursor : null);
      this.draft[i] = { date, time: minutes };
      if (this.range && i === 1 && !this.draft[0]) this.draft[0] = { date, time: this.defaultTime(0) };
      if (advance && this.range && i === 0) this.clockEnd = 1;
      this.render();
    }

    finish() {
      this.commit();
      this.close(true);
    }

    /* ---------- rendering the picker ---------- */
    render() {
      this.renderPresets();
      if (this.hasDate) this.renderCalendar();
      if (this.hasTime) this.renderClock();
      this.renderFoot();
    }

    renderPresets() {
      const presets = this.presets();
      this.presetList.hidden = !this.range || !this.hasDate || !presets.length;
      this.presetList.textContent = "";
      const [a, b] = this.draft;
      presets.forEach(([label, span]) => {
        const button = el("button", "pc-date-preset", { type: "button", "data-preset": JSON.stringify(span) });
        button.textContent = label;
        button.setAttribute("aria-pressed", String(!!(a?.date && b?.date && ymd(a.date) === span[0] && ymd(b.date) === span[1])));
        this.presetList.appendChild(button);
      });
    }

    renderCalendar() {
      const c = this.calendar;
      c.textContent = "";
      const head = el("div", "pc-date-head");
      const title = el("button", "pc-date-title", { type: "button", "data-nav": "up", id: `${this.id}Title`, "aria-live": "polite" });
      const prev = el("button", "pc-date-nav", { type: "button", "data-nav": "prev" });
      const next = el("button", "pc-date-nav", { type: "button", "data-nav": "next" });
      // the head row mirrors on RTL pages, so the carets swap to keep pointing outwards
      prev.appendChild(el("i", `fa-regular fa-chevron-${pc.rtl() ? "right" : "left"}`, { "aria-hidden": "true" }));
      next.appendChild(el("i", `fa-regular fa-chevron-${pc.rtl() ? "left" : "right"}`, { "aria-hidden": "true" }));
      head.append(prev, title, next);
      c.appendChild(head);
      const year = this.view.getFullYear();
      const today = this.now().date;

      if (this.viewMode === "days") {
        title.textContent = format({ date: this.view }, "F Y", this.locale);
        title.setAttribute("aria-label", pc.t(":month, choose a month", { month: title.textContent }));
        prev.setAttribute("aria-label", pc.t("Previous month"));
        next.setAttribute("aria-label", pc.t("Next month"));
        const grid = el("div", "pc-date-grid", { role: "grid", "aria-labelledby": title.id });
        const names = el("div", "pc-date-row", { role: "row" });
        const offset = (this.view.getDay() - this.weekStart + 7) % 7;
        const start = addDays(this.view, -offset);
        for (let i = 0; i < 7; i++) {
          const d = addDays(start, i);
          const cell = el("span", "pc-date-weekday", { role: "columnheader", "aria-label": name(this.locale, "l", d) });
          cell.textContent = name(this.locale, "D", d).slice(0, 2);
          names.appendChild(cell);
        }
        grid.appendChild(names);
        // always six weeks, so the picker keeps its height from month to month
        for (let w = 0; w < 6; w++) {
          const row = el("div", "pc-date-row", { role: "row" });
          for (let i = 0; i < 7; i++) {
            const d = addDays(start, w * 7 + i);
            const button = el("button", "pc-date-day", {
              type: "button",
              role: "gridcell",
              tabindex: sameDay(d, this.cursor) ? "0" : "-1",
              "aria-label": format({ date: d }, "l, j F Y", this.locale),
              "data-date": ymd(d),
            });
            button.textContent = d.getDate();
            if (d.getMonth() !== this.view.getMonth()) button.dataset.outside = "";
            if (sameDay(d, today)) {
              button.dataset.today = "";
              button.setAttribute("aria-current", "date");
            }
            if (!this.dayAllowed(d)) button.disabled = true;
            row.appendChild(button);
          }
          grid.appendChild(row);
        }
        c.appendChild(grid);
        this.markDays();
        return;
      }

      const grid = el("div", "pc-date-cells");
      const chosen = this.draft[0]?.date;
      if (this.viewMode === "months") {
        title.textContent = String(year);
        title.setAttribute("aria-label", pc.t(":year, choose a year", { year }));
        prev.setAttribute("aria-label", pc.t("Previous year"));
        next.setAttribute("aria-label", pc.t("Next year"));
        for (let m = 0; m < 12; m++) {
          const first = day(year, m, 1);
          const last = day(year, m + 1, 0);
          const button = el("button", "pc-date-cell", { type: "button", "data-month": m, "aria-label": format({ date: first }, "F Y", this.locale) });
          button.textContent = format({ date: first }, "M", this.locale);
          button.setAttribute("aria-selected", String(!!chosen && chosen.getFullYear() === year && chosen.getMonth() === m));
          if (today.getFullYear() === year && today.getMonth() === m) button.dataset.today = "";
          button.disabled = !this.spanAllowed(first, last);
          grid.appendChild(button);
        }
      } else {
        const from = year - (year % 12);
        title.textContent = `${from} – ${from + 11}`;
        title.disabled = true;
        prev.setAttribute("aria-label", pc.t("Previous years"));
        next.setAttribute("aria-label", pc.t("Next years"));
        for (let y = from; y < from + 12; y++) {
          const button = el("button", "pc-date-cell", { type: "button", "data-year": y });
          button.textContent = y;
          button.setAttribute("aria-selected", String(!!chosen && chosen.getFullYear() === y));
          if (today.getFullYear() === y) button.dataset.today = "";
          button.disabled = !this.spanAllowed(day(y, 0, 1), day(y, 11, 31));
          grid.appendChild(button);
        }
      }
      c.appendChild(grid);
    }

    /** Marks the chosen day(s) and the span between without rebuilding the grid. */
    markDays() {
      const [a, b] = this.draft;
      let start = a?.date || null;
      let end = this.range ? b?.date || (this.picking === 1 ? this.hover : null) : null;
      if (start && end && dayNumber(end) < dayNumber(start)) [start, end] = [end, start];
      const span = !!(start && end && !sameDay(start, end));
      this.calendar.querySelectorAll(".pc-date-day").forEach((button) => {
        const n = dayNumber(parseDate(button.dataset.date));
        const isStart = !!start && n === dayNumber(start);
        const isEnd = !!end && n === dayNumber(end);
        flag(button, "rangeStart", span && isStart);
        flag(button, "rangeEnd", span && isEnd);
        flag(button, "inRange", span && n > dayNumber(start) && n < dayNumber(end));
        button.setAttribute("aria-selected", String(isStart || isEnd));
      });
    }

    renderClock() {
      const c = this.clock;
      c.textContent = "";
      if (this.range) {
        const ends = el("div", "pc-tabs pc-tabs-soft pc-date-ends", { role: "tablist", "aria-label": pc.t("Which time") });
        [pc.t("Start"), pc.t("End")].forEach((label, i) => {
          const p = this.draft[i];
          const tab = el("button", `pc-tab${i === this.clockEnd ? " pc-tab-active" : ""}`, { type: "button", role: "tab", "data-end": i, "aria-selected": String(i === this.clockEnd) });
          const time = el("span", "pc-date-end-time");
          time.textContent = p?.time != null ? format({ date: null, time: p.time }, this.twelveHour ? "g:i A" : "H:i", this.locale) : "--:--";
          tab.append(label, time);
          ends.appendChild(tab);
        });
        c.appendChild(ends);
      }
      const p = this.clockPoint();
      const date = p?.date ?? (this.hasDate ? this.draft[0]?.date ?? null : null);
      const time = p?.time ?? null;
      const cols = el("div", "pc-date-cols");
      const hours = el("div", "pc-date-col", { role: "listbox", "aria-label": pc.t("Hour") });
      for (let h = 0; h < 24; h++) {
        const option = el("button", "pc-date-option", { type: "button", role: "option", "data-hour": h, "aria-selected": String(time !== null && Math.floor(time / 60) === h) });
        option.textContent = this.twelveHour ? `${h % 12 || 12} ${h < 12 ? "AM" : "PM"}` : pad(h);
        option.disabled = !this.timeAllowed(date, h * 60, h * 60 + 59);
        hours.appendChild(option);
      }
      const minutes = el("div", "pc-date-col", { role: "listbox", "aria-label": pc.t("Minute") });
      const hour = time === null ? Math.floor(this.defaultTime(this.clockEnd) / 60) : Math.floor(time / 60);
      const offered = new Set();
      for (let m = 0; m < 60; m += this.step) offered.add(m);
      if (time !== null) offered.add(time % 60); // a value off the step (23:59) still shows as chosen
      [...offered].sort((x, y) => x - y).forEach((m) => {
        const option = el("button", "pc-date-option", { type: "button", role: "option", "data-minute": m, "aria-selected": String(time !== null && time % 60 === m) });
        option.textContent = pad(m);
        option.disabled = !this.timeAllowed(date, hour * 60 + m, hour * 60 + m);
        minutes.appendChild(option);
      });
      cols.append(hours, minutes);
      c.appendChild(cols);
      // bring the chosen hour and minute into view inside their columns, not the page
      [hours, minutes].forEach((col) => {
        const chosen = col.querySelector('[aria-selected="true"]') || col.querySelector(`[data-hour="${hour}"]`);
        if (chosen) col.scrollTop = chosen.offsetTop - col.clientHeight / 2 + chosen.offsetHeight / 2;
      });
    }

    renderFoot() {
      const [a, b] = this.draft;
      let text = "";
      if (this.range && this.hasDate && !a) text = pc.t("Pick the first day");
      else if (this.range && this.hasDate && this.picking === 1) text = pc.t("Pick the last day");
      else if (this.range) text = formatRange(a, b, this.pattern, this.locale);
      else if (this.hasTime) text = format(a, this.pattern, this.locale);
      this.summary.textContent = text;
      this.doneButton.disabled = !this.draftComplete();
    }

    focusCursor() {
      const target = this.calendar.querySelector('.pc-date-day[tabindex="0"]') || this.calendar.querySelector("button:not(:disabled)");
      if (target) target.focus({ preventScroll: true });
    }

    /* ---------- calendar navigation ---------- */
    navigate(where) {
      if (where === "up") this.viewMode = this.viewMode === "days" ? "months" : "years";
      else {
        const step = where === "next" ? 1 : -1;
        this.view = addMonths(this.view, this.viewMode === "days" ? step : this.viewMode === "months" ? step * 12 : step * 144);
      }
      this.renderCalendar();
      this.place();
      const keep = this.calendar.querySelector(`[data-nav="${where}"]`);
      if (keep && !keep.disabled) keep.focus({ preventScroll: true });
    }

    showMonth(m) {
      this.view = day(this.view.getFullYear(), m, 1);
      this.viewMode = "days";
      if (this.cursor.getMonth() !== m || this.cursor.getFullYear() !== this.view.getFullYear()) this.cursor = this.clampDay(this.view);
      this.renderCalendar();
      this.place();
      this.focusCursor();
    }

    showYear(y) {
      this.view = day(y, this.view.getMonth(), 1);
      this.viewMode = "months";
      this.renderCalendar();
      const month = this.calendar.querySelector(`[data-month="${this.view.getMonth()}"]`);
      if (month) month.focus({ preventScroll: true });
    }

    moveCursor(d) {
      d = this.clampDay(d);
      this.cursor = d;
      if (d.getMonth() !== this.view.getMonth() || d.getFullYear() !== this.view.getFullYear()) {
        this.view = day(d.getFullYear(), d.getMonth(), 1);
        this.renderCalendar();
      } else {
        this.calendar.querySelectorAll(".pc-date-day").forEach((button) => (button.tabIndex = button.dataset.date === ymd(d) ? 0 : -1));
      }
      if (this.range && this.picking === 1) {
        this.hover = d;
        this.markDays();
      }
      this.focusCursor();
    }

    onCalendarKey(e) {
      if (!e.target.classList.contains("pc-date-day")) return;
      const d = this.cursor;
      const back = pc.rtl() ? 1 : -1; // the grid mirrors on RTL pages, so Left moves forward there
      const keys = {
        ArrowLeft: () => addDays(d, back),
        ArrowRight: () => addDays(d, -back),
        ArrowUp: () => addDays(d, -7),
        ArrowDown: () => addDays(d, 7),
        Home: () => addDays(d, -((d.getDay() - this.weekStart + 7) % 7)),
        End: () => addDays(d, 6 - ((d.getDay() - this.weekStart + 7) % 7)),
        PageUp: () => addMonths(d, e.shiftKey ? -12 : -1),
        PageDown: () => addMonths(d, e.shiftKey ? 12 : 1),
      };
      if (!keys[e.key]) return;
      e.preventDefault();
      this.moveCursor(keys[e.key]());
    }

    /* ---------- open / close / place ---------- */
    isOpen() {
      return current === this;
    }

    open() {
      if (this.isOpen() || this.disabled()) return;
      if (current) current.close(false, true);
      current = this;
      this.startDraft();
      this.sheet = sheetQuery.matches;
      this.flyout.dataset.mode = this.sheet ? "sheet" : "pop";
      this.flyout.hidden = false;
      this.backdrop.hidden = !this.sheet;
      this.trigger.setAttribute("aria-expanded", "true");
      this.render();
      this.place();
      if (this.sheet) pc.lockScroll(true);
      if (this.hasDate) this.focusCursor();
      else (this.clock.querySelector('.pc-date-option[aria-selected="true"]') || this.clock.querySelector(".pc-date-option:not(:disabled)"))?.focus({ preventScroll: true });

      this.unOutside = pc.onOutsideClick(this.root, () => this.close(false, true));
      this.reposition = (e) => {
        if (e && e.type === "scroll" && this.flyout.contains(e.target)) return;
        const r = this.trigger.getBoundingClientRect();
        if (!this.sheet && (r.bottom < 0 || r.top > window.innerHeight)) this.close(false, true);
        else this.place();
      };
      window.addEventListener("scroll", this.reposition, true);
      window.addEventListener("resize", this.reposition);
    }

    /** Keep: write the edit to the inputs if it's finished (Done, a click outside, Tab); otherwise it's dropped. */
    close(refocus = false, keep = false) {
      if (!this.isOpen()) return;
      current = null;
      if (keep && this.draftComplete()) this.commit();
      this.flyout.hidden = true;
      this.backdrop.hidden = true;
      this.trigger.setAttribute("aria-expanded", "false");
      if (this.sheet) pc.lockScroll(false);
      this.unOutside && this.unOutside();
      window.removeEventListener("scroll", this.reposition, true);
      window.removeEventListener("resize", this.reposition);
      if (refocus) this.trigger.focus();
    }

    /** Below the field when it fits or there's more room below, otherwise above; never off-screen. */
    place() {
      const f = this.flyout.style;
      if (this.sheet !== sheetQuery.matches) {
        this.sheet = sheetQuery.matches;
        this.flyout.dataset.mode = this.sheet ? "sheet" : "pop";
        this.backdrop.hidden = !this.sheet;
        pc.lockScroll(this.sheet);
      }
      if (this.sheet) {
        f.top = f.left = f.maxHeight = "";
        return;
      }
      const r = this.trigger.getBoundingClientRect();
      const vw = window.innerWidth;
      const vh = window.innerHeight;
      f.maxHeight = "none";
      const width = Math.min(this.flyout.offsetWidth, vw - EDGE * 2);
      const start = pc.rtl() ? r.right - width : r.left; // the field's inline-start edge
      f.left = Math.min(Math.max(EDGE, start), vw - width - EDGE) + "px";
      const natural = this.flyout.offsetHeight;
      const below = vh - r.bottom - GAP - EDGE;
      const above = r.top - GAP - EDGE;
      const down = below >= natural || below >= above;
      const height = Math.min(natural, down ? below : above);
      f.maxHeight = Math.max(height, 160) + "px";
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

  function flag(node, key, on) {
    if (on) node.dataset[key] = "";
    else delete node.dataset[key];
  }

  const instances = new WeakMap();

  function enhance(root) {
    const scope = root && root.querySelectorAll ? root : document;
    const found = scope.matches && scope.matches(".pc-date") ? [scope] : [];
    found.concat(Array.from(scope.querySelectorAll(".pc-date"))).forEach((field) => {
      if (instances.has(field) || !field.querySelector(":scope > input")) return;
      instances.set(field, new DatePicker(field));
    });
  }

  window.pcDate = {
    enhance,
    set: (field, start, end) => instances.get(field)?.write([start ?? "", end ?? ""]),
    close: () => current && current.close(),
  };

  function start() {
    enhance(document);
    // fields added later (page scripts, fetched fragments) are picked up as they arrive
    new MutationObserver((records) => {
      records.forEach((r) => r.addedNodes.forEach((n) => n.nodeType === 1 && enhance(n)));
    }).observe(document.body, { childList: true, subtree: true });
  }

  document.readyState === "loading" ? document.addEventListener("DOMContentLoaded", start) : start();
})();
