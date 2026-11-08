/**
 * Copilot — the chat-driven AI form editor.
 *
 * Lives inside the creator's settings sidebar (property grid) as a page of
 * its own, toggled by a "Copilot" button in the vertical tab strip, so the
 * user can prompt without leaving the Designer tab.
 *
 * Flow (one chat message = one turn):
 *   1. the panel posts {instruction, document, history} to forms.copilot
 *   2. the backend (CopilotEditor) runs the tool-calling loop and returns a
 *      list of operations — the document is never mutated server-side
 *   3. the proposal is shown in the thread with Apply/Discard (or applied
 *      immediately in Auto mode); applyOperations() maps each op onto the
 *      SurveyJS Creator API as ONE undo transaction.
 */
window.React = { createElement: SurveyUI.createElement };

const COPILOT_PAGE_ID = "copilot";
const COPILOT_ENDPOINT = "@route('forms.copilot')";

Survey.SvgRegistry.registerIcon(
  "icon-copilot",
  '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">' +
    '<path d="M10 4l1.9 5.1L17 11l-5.1 1.9L10 18l-1.9-5.1L3 11l5.1-1.9L10 4z"/>' +
    '<path d="M18 2l.95 2.55L21.5 5.5l-2.55.95L18 9l-.95-2.55L14.5 5.5l2.55-.95L18 2z"/>' +
    '<path d="M18 15l.7 1.8 1.8.7-1.8.7L18 20l-.7-1.8-1.8-.7 1.8-.7L18 15z"/>' +
  '</svg>'
);

/* ------------------------------------------------------------------------
 * Schema — SurveyJS's own metadata is the source of truth for which element
 * types exist and what properties each one has; sent with every request so
 * the model never has to guess at property names.
 * --------------------------------------------------------------------- */

function copilotProperties(type) {
  return Survey.Serializer.getProperties(type).filter(p => p.visible && p.isSerializable);
}

// "rateType:string(labels|stars|smileys)"
function copilotPropertySummary(prop) {
  let choices = null;
  try { choices = prop.choices; } catch (e) { /* choices computed from an object we don't have */ }
  const list = Array.isArray(choices) && choices.length > 0 && choices.length <= 12
    ? "(" + choices.map(c => (c && c.value !== undefined ? c.value : c)).join("|") + ")"
    : "";
  return prop.name + ":" + (prop.type || "string") + list;
}

function buildTypeSchema(survey) {
  const common = copilotProperties("question");
  const commonNames = new Set(common.map(p => p.name));

  const present = {};
  survey.getAllQuestions().concat(survey.getAllPanels()).forEach(el => { present[el.getType()] = true; });
  const own = {};
  Object.keys(present).sort().forEach(type => {
    own[type] = copilotProperties(type).filter(p => !commonNames.has(p.name)).map(copilotPropertySummary);
  });

  return {
    types: Survey.ElementFactory.Instance.getAllTypes(),
    common: common.map(copilotPropertySummary),
    present: own
  };
}

/* ------------------------------------------------------------------------
 * Operations → SurveyJS Creator API
 * --------------------------------------------------------------------- */

function copilotFindElement(survey, name) {
  return survey.getQuestionByName(name) || survey.getPanelByName(name) || null;
}

function copilotIndexOf(names, before, after) {
  if (before !== undefined) {
    const at = names.indexOf(before);
    return at === -1 ? undefined : at;
  }
  if (after !== undefined) {
    const at = names.indexOf(after);
    return at === -1 ? undefined : at + 1;
  }
  return undefined;
}

// Every {name} referenced by a condition/expression property must exist at this point of the batch
function copilotValidateConditions(properties, type, survey, opLabel) {
  Survey.Serializer.getProperties(type).forEach(prop => {
    if (prop.type !== "condition" && prop.type !== "expression") return;
    const expr = properties[prop.name];
    if (typeof expr !== "string" || !expr.trim()) return;
    (expr.match(/\{([^{}]+)\}/g) || []).forEach(ref => {
      const name = ref.slice(1, -1).trim().split(/[.\[]/)[0].trim();
      if (!name || /^(row|panel|composite|self)$/i.test(name)) return;
      if (copilotFindElement(survey, name)) return;
      if (survey.calculatedValues.some(cv => cv.name === name)) return;
      throw new Error(`${opLabel}: ${prop.name} ("${expr}") references "${name}", which does not exist. Add it earlier in the same batch, or reference a real question.`);
    });
  });
}

// Shallow merge: every key in the patch overwrites, keys left out are untouched.
// Goes through the property setters (not fromJSON, whose "loading" mode
// bypasses the creator's undo/modified tracking).
function copilotPatch(element, patch, opLabel) {
  const props = Object.keys(patch).map(key => {
    const prop = Survey.Serializer.findProperty(element.getType(), key);
    if (!prop) throw new Error(`${opLabel}: "${key}" is not a property of ${element.getType()} "${element.name}".`);
    return prop;
  });
  props.forEach(prop => {
    const value = patch[prop.name];
    if (value === null || value === undefined) element.resetPropertyValue(prop.name);
    else if (prop.className) new Survey.JsonObject().valueToObj(value, element, prop); // typed values (choices, validators, columns...)
    else element[prop.name] = value;
  });
}

function copilotCreateElement(spec, opLabel) {
  const type = typeof spec.type === "string" ? spec.type : "";
  if (!Survey.Serializer.isDescendantOf(type, "question") && !Survey.Serializer.isDescendantOf(type, "panel")) {
    throw new Error(`${opLabel}: "${type}" is not a real question type.`);
  }
  if (!spec.name) throw new Error(`${opLabel}: the new element needs a name.`);
  const element = Survey.Serializer.createClass(type);
  element.fromJSON(spec);
  return element;
}

function copilotApplyOperation(creator, op) {
  const survey = creator.survey;
  const label = `Copilot op "${op.op}"`;

  switch (op.op) {
    case "updateQuestion": {
      const element = copilotFindElement(survey, op.name);
      if (!element) throw new Error(`${label}: question "${op.name}" does not exist.`);
      copilotValidateConditions(op.patch, element.getType(), survey, label);
      copilotPatch(element, op.patch, label);
      return;
    }
    case "updatePage": {
      const page = survey.getPageByName(op.name);
      if (!page) throw new Error(`${label}: page "${op.name}" does not exist.`);
      copilotValidateConditions(op.patch, "page", survey, label);
      copilotPatch(page, op.patch, label);
      return;
    }
    case "addQuestion": {
      const page = survey.getPageByName(op.pageName) || survey.getPanelByName(op.pageName);
      if (!page) throw new Error(`${label}: page "${op.pageName}" does not exist.`);
      if (copilotFindElement(survey, op.spec.name)) throw new Error(`${label}: "${op.spec.name}" already exists.`);
      const element = copilotCreateElement(op.spec, label);
      copilotValidateConditions(op.spec, element.getType(), survey, label);
      const index = copilotIndexOf(page.elements.map(el => el.name), op.beforeQuestionName, op.afterQuestionName);
      page.addElement(element, index === undefined ? -1 : index);
      return;
    }
    case "removeQuestion": {
      const element = copilotFindElement(survey, op.name);
      if (!element) throw new Error(`${label}: question "${op.name}" does not exist.`);
      creator.deleteElement(element);
      return;
    }
    case "addPage": {
      if (!op.spec.name) throw new Error(`${label}: the new page needs a name.`);
      if (survey.getPageByName(op.spec.name)) throw new Error(`${label}: page "${op.spec.name}" already exists.`);
      copilotValidateConditions(op.spec, "page", survey, label);
      const page = Survey.Serializer.createClass("page");
      page.fromJSON(op.spec);
      const index = copilotIndexOf(survey.pages.map(p => p.name), op.beforePageName, op.afterPageName);
      survey.addPage(page, index);
      return;
    }
    case "removePage": {
      const page = survey.getPageByName(op.name);
      if (!page) throw new Error(`${label}: page "${op.name}" does not exist.`);
      creator.deleteElement(page);
      return;
    }
    case "moveQuestion": {
      const element = copilotFindElement(survey, op.name);
      if (!element) throw new Error(`${label}: question "${op.name}" does not exist.`);
      const target = survey.getPageByName(op.toPageName) || survey.getPanelByName(op.toPageName);
      if (!target) throw new Error(`${label}: target page "${op.toPageName}" does not exist.`);
      element.parent.removeElement(element);
      const index = copilotIndexOf(target.elements.map(el => el.name), op.beforeQuestionName, op.afterQuestionName);
      target.addElement(element, index === undefined ? -1 : index);
      return;
    }
    case "movePage": {
      const page = survey.getPageByName(op.name);
      if (!page) throw new Error(`${label}: page "${op.name}" does not exist.`);
      survey.pages.splice(survey.pages.indexOf(page), 1);
      const index = copilotIndexOf(survey.pages.map(p => p.name), op.beforePageName, op.afterPageName);
      survey.pages.splice(index === undefined ? survey.pages.length : index, 0, page);
      return;
    }
    case "updateDocument": {
      Object.keys(op.patch).forEach(key => {
        if (key !== "title" && key !== "description") throw new Error(`${label}: "${key}" is not a supported survey-level property. Known properties: title, description.`);
        survey[key] = op.patch[key];
      });
      return;
    }
    default:
      throw new Error(`Copilot: unknown operation "${op.op}".`);
  }
}

/**
 * Applies a whole batch in order (a later op may reference something an
 * earlier one just created) as ONE undo-stack entry. Atomic: if any op
 * fails, everything already applied is undone before the error is rethrown.
 */
function applyOperations(creator, operations) {
  if (!operations.length) return;
  const undoRedo = creator.undoRedoManager;
  const before = undoRedo._transactions.length;

  undoRedo.startTransaction("copilot");
  try {
    operations.forEach(op => copilotApplyOperation(creator, op));
    undoRedo.stopTransaction();
  } catch (error) {
    undoRedo.stopTransaction();
    if (undoRedo._transactions.length > before) creator.undo();
    throw error;
  }
}

/* ------------------------------------------------------------------------
 * Chat state — lives outside the panel so the thread survives the sidebar
 * switching pages (the panel unmounts whenever another page is shown)
 * --------------------------------------------------------------------- */

function describeOperations(operations) {
  if (operations.length === 0) return "No changes proposed.";
  const parts = operations.map(op => {
    switch (op.op) {
      case "updateQuestion": return `update ${op.name}`;
      case "updatePage": return `update page ${op.name}`;
      case "addQuestion": return `add question ${op.spec.name}`;
      case "removeQuestion": return `remove ${op.name}`;
      case "addPage": return `add page ${op.spec.name}`;
      case "removePage": return `remove page ${op.name}`;
      case "moveQuestion": return `move ${op.name}`;
      case "movePage": return `move page ${op.name}`;
      case "updateDocument": return "update form title/description";
      default: return op.op;
    }
  });
  return `${operations.length} change${operations.length === 1 ? "" : "s"}: ${parts.join(", ")}`;
}

// The most recent op whose target still exists once the batch has run — selected on canvas after applying
function primaryTargetOf(operations) {
  for (let i = operations.length - 1; i >= 0; i--) {
    const op = operations[i];
    switch (op.op) {
      case "updateQuestion":
      case "moveQuestion": return { kind: "question", name: op.name };
      case "addQuestion": return { kind: "question", name: op.spec.name };
      case "updatePage":
      case "movePage": return { kind: "page", name: op.name };
      case "addPage": return { kind: "page", name: op.spec.name };
      default: continue;
    }
  }
  return undefined;
}

// Plain {role, text} turns for the backend; a proposal's outcome is the most useful context for a follow-up correction
function buildHistory(thread) {
  return thread.map(message => {
    if (message.role === "user") return { role: "user", text: message.text };
    const outcome = message.applied ? " (the user applied this change)" : message.discarded ? " (the user discarded this proposal — it was not what they wanted)" : "";
    return { role: "assistant", text: message.role === "error" ? `Error: ${message.text}` : `${message.text}${outcome}` };
  });
}

class CopilotStore {
  constructor(creator) {
    this.creator = creator;
    this.thread = [];
    this.input = "";
    this.busy = false;
    this.mode = "manual"; // or "auto": proposals apply the moment they arrive
    this.listeners = [];
    this.nextId = 0;
  }

  subscribe(listener) {
    this.listeners.push(listener);
    return () => { this.listeners = this.listeners.filter(l => l !== listener); };
  }

  notify() {
    this.listeners.forEach(listener => listener());
  }

  push(message) {
    const id = this.nextId++;
    this.thread = this.thread.concat([Object.assign({ id }, message)]);
    this.notify();
    return id;
  }

  update(id, changes) {
    this.thread = this.thread.map(m => (m.id === id ? Object.assign({}, m, changes) : m));
    this.notify();
  }

  setInput(input) { this.input = input; this.notify(); }
  setMode(mode) { this.mode = mode; this.notify(); }
  clear() { this.thread = []; this.notify(); }

  async send() {
    const instruction = this.input.trim();
    if (instruction === "" || this.busy) return;

    const history = buildHistory(this.thread);
    this.input = "";
    this.push({ role: "user", text: instruction });
    this.busy = true;
    this.notify();

    try {
      const response = await fetch(COPILOT_ENDPOINT, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          instruction,
          document: this.creator.JSON,
          schema: buildTypeSchema(this.creator.survey),
          history,
          _token: document.querySelector('meta[name="csrf-token"]').getAttribute("content")
        })
      });

      const result = await response.json();
      if (!result.status) {
        this.push({ role: "error", text: result.message || "Copilot request failed." });
      } else if (!Array.isArray(result.operations) || result.operations.length === 0) {
        this.push({ role: "ai", text: result.message || "No changes proposed for that instruction." });
      } else {
        const id = this.push({ role: "ai", text: result.note || describeOperations(result.operations), operations: result.operations });
        if (this.mode === "auto") this.applyMessage(id, result.operations, true);
      }
    } catch (error) {
      this.push({ role: "error", text: "Copilot request failed. Please try again." });
    } finally {
      this.busy = false;
      this.notify();
    }
  }

  applyMessage(id, operations, auto) {
    try {
      applyOperations(this.creator, operations);
      this.update(id, { applied: true, autoApplied: auto });

      const target = primaryTargetOf(operations);
      const survey = this.creator.survey;
      const element = !target ? null : target.kind === "page" ? survey.getPageByName(target.name) : copilotFindElement(survey, target.name);
      if (element) this.creator.selectElement(element, null, false);
    } catch (error) {
      this.push({ role: "error", text: error instanceof Error ? error.message : String(error) });
    }
  }

  apply(id) {
    const message = this.thread.find(m => m.id === id);
    if (message && message.operations && !message.applied) this.applyMessage(id, message.operations, false);
  }

  discard(id) {
    this.update(id, { operations: undefined, discarded: true });
  }
}

/* ------------------------------------------------------------------------
 * Panel UI
 * --------------------------------------------------------------------- */

const h = SurveyUI.createElement;
const COPILOT_INPUT_MAX_HEIGHT = 130;
const COPILOT_THINKING_WORDS = ["Thinking", "Digging", "Cooking", "Pondering", "Sketching", "Untangling", "Assembling", "Scheming"];

function CopilotSpark(props) {
  return h("span", { className: "svc-copilot__spark" + (props.className ? " " + props.className : "") },
    h("svg", { viewBox: "0 0 24 24" }, h("use", { xlinkHref: "#icon-copilot" }))
  );
}

function CopilotMessageRow(props) {
  const { message, store } = props;

  if (message.role === "user") {
    return h("div", { className: "svc-copilot__message svc-copilot__message--user" }, message.text);
  }

  const isError = message.role === "error";
  const children = [h("div", { className: "svc-copilot__message-text" }, message.text)];

  if (message.operations && !message.applied) {
    children.push(
      h("div", { className: "svc-copilot__message-actions" },
        h("button", { type: "button", className: "svc-copilot__btn svc-copilot__btn--primary", onClick: () => store.apply(message.id) }, "Apply"),
        h("button", { type: "button", className: "svc-copilot__btn", onClick: () => store.discard(message.id) }, "Discard")
      )
    );
  }
  if (message.applied) {
    children.push(h("div", { className: "svc-copilot__applied" }, (message.autoApplied ? "Applied automatically" : "Applied") + " · undo from the toolbar"));
  }
  if (message.discarded) {
    children.push(h("div", { className: "svc-copilot__discarded" }, "Discarded"));
  }

  return h("div", { className: "svc-copilot__message svc-copilot__message--ai" + (isError ? " svc-copilot__message--error" : "") },
    h(CopilotSpark, { className: isError ? "svc-copilot__spark--error" : "" }),
    h("div", { className: "svc-copilot__message-body" }, ...children)
  );
}

class CopilotPanel extends SurveyUI.Component {
  constructor(props) {
    super(props);
    this.store = props.model.store;
    this.state = { tick: 0, thinkingWord: COPILOT_THINKING_WORDS[0], settingsOpen: false };
    this.threadRef = SurveyUI.createRef();
    this.textareaRef = SurveyUI.createRef();
  }

  componentDidMount() {
    this.unsubscribe = this.store.subscribe(() => this.setState({ tick: this.state.tick + 1 }));
    this.scrollToBottom();
    this.resizeInput();
    this.syncThinking();
  }

  componentDidUpdate() {
    this.scrollToBottom();
    this.resizeInput();
    this.syncThinking();
  }

  componentWillUnmount() {
    if (this.unsubscribe) this.unsubscribe();
    this.stopThinking();
  }

  scrollToBottom() {
    const el = this.threadRef.current;
    if (el) el.scrollTop = el.scrollHeight;
  }

  // Auto-growing textarea, up to a few lines then it scrolls
  resizeInput() {
    const el = this.textareaRef.current;
    if (!el) return;
    el.style.height = "auto";
    el.style.height = Math.min(el.scrollHeight, COPILOT_INPUT_MAX_HEIGHT) + "px";
  }

  syncThinking() {
    if (this.store.busy && !this.thinkingTimer) {
      this.thinkingTimer = setInterval(() => {
        const words = COPILOT_THINKING_WORDS.filter(w => w !== this.state.thinkingWord);
        this.setState({ thinkingWord: words[Math.floor(Math.random() * words.length)] });
      }, 1100);
    } else if (!this.store.busy) {
      this.stopThinking();
    }
  }

  stopThinking() {
    if (this.thinkingTimer) {
      clearInterval(this.thinkingTimer);
      this.thinkingTimer = null;
    }
  }

  handleKeyDown(event) {
    // Enter sends, Shift+Enter inserts a newline
    if (event.key === "Enter" && !event.shiftKey) {
      event.preventDefault();
      this.store.send();
    }
  }

  renderThread() {
    const store = this.store;
    if (this.state.settingsOpen) {
      return h("div", { className: "svc-copilot__settings" },
        h("div", { className: "svc-copilot__settings-title" }, "SETTINGS"),
        h("button", { type: "button", className: "svc-copilot__settings-item", onClick: () => { store.clear(); this.setState({ settingsOpen: false }); } }, "Clear conversation")
      );
    }

    const rows = store.thread.map(message => h(CopilotMessageRow, { key: message.id, message, store }));
    if (store.busy) {
      rows.push(h("div", { key: "thinking", className: "svc-copilot__thinking" }, h(CopilotSpark, { className: "svc-copilot__spark--pulse" }), this.state.thinkingWord + "…"));
    }
    if (rows.length === 0) {
      rows.push(
        h("div", { key: "empty", className: "svc-copilot__empty" },
          h(CopilotSpark, { className: "svc-copilot__spark--large" }),
          h("div", { className: "svc-copilot__empty-title" }, "Ask Copilot to edit this form"),
          h("div", { className: "svc-copilot__empty-hint" }, "e.g. “add an email question after the name”, “make the rating a 10-point scale”, “show the comments box only when the rating is below 6”")
        )
      );
    }
    return h("div", { className: "svc-copilot__thread", ref: this.threadRef }, ...rows);
  }

  render() {
    const store = this.store;
    const canSend = !store.busy && store.input.trim() !== "";
    const auto = store.mode === "auto";

    return h("div", { className: "svc-copilot" },
      this.renderThread(),
      h("div", { className: "svc-copilot__composer" },
        h("textarea", {
          ref: this.textareaRef,
          className: "svc-copilot__input",
          rows: 1,
          value: store.input,
          placeholder: "Ask Copilot to edit this form…",
          onInput: event => store.setInput(event.target.value),
          onFocus: () => { if (this.state.settingsOpen) this.setState({ settingsOpen: false }); },
          onKeyDown: this.handleKeyDown.bind(this)
        }),
        h("div", { className: "svc-copilot__composer-actions" },
          h("button", {
            type: "button",
            className: "svc-copilot__icon-btn" + (this.state.settingsOpen ? " svc-copilot__icon-btn--active" : ""),
            title: "Copilot settings",
            onClick: () => this.setState({ settingsOpen: !this.state.settingsOpen })
          }, h(CopilotSpark)),
          h("div", { className: "svc-copilot__composer-spacer" }),
          h("button", {
            type: "button",
            className: "svc-copilot__mode" + (auto ? " svc-copilot__mode--auto" : ""),
            title: auto ? "Auto: proposals apply immediately. Click to switch to Manual." : "Manual: proposals wait for your Apply click. Click to switch to Auto.",
            onClick: () => store.setMode(auto ? "manual" : "auto")
          }, auto ? "Auto" : "Manual"),
          h("button", {
            type: "button",
            className: "svc-copilot__send",
            title: "Send",
            disabled: !canSend,
            onClick: () => store.send()
          }, h("svg", { viewBox: "0 0 24 24" }, h("path", { d: "M3 11.5l17-8-5.5 17-3.2-6.4L3 11.5z" })))
        )
      )
    );
  }
}

SurveyUI.ReactElementFactory.Instance.registerElement(
  "svc-copilot-panel",
  (props) => SurveyUI.createElement(CopilotPanel, props)
);

/* ------------------------------------------------------------------------
 * Sidebar integration
 * --------------------------------------------------------------------- */

/**
 * Registers the copilot page and its toggle button on the creator's settings
 * sidebar. Must run before creator.render().
 */
function installCopilot(creator) {
  const sidebar = creator.sidebar;
  const designer = creator.getPlugin("designer");
  const store = new CopilotStore(creator);
  let previousPageId = null;

  const isOpen = () => sidebar.activePage === COPILOT_PAGE_ID;

  const setHeader = () => {
    sidebar.header.componentName = "svc-side-bar-header";
    sidebar.header.componentData = sidebar.header;
    sidebar.header.title = "Copilot";
    sidebar.header.subTitle = "AI assistant";
  };

  const open = () => {
    sidebar.expandSidebar();
    if (!isOpen()) previousPageId = sidebar.activePage;
    sidebar.activePage = COPILOT_PAGE_ID;
  };

  const close = (pageId) => {
    const target = pageId || previousPageId;
    if (target) designer.setActivePage(target);
    else designer.updateActivePage();
  };

  const page = sidebar.addPage(COPILOT_PAGE_ID, "svc-copilot-panel", { creator, store, open });
  page.caption = "Copilot";
  page.activateCallback = () => { setHeader(); button.active = true; };
  page.deactivateCallback = () => {
    button.active = false;
    sidebar.header.subTitle = designer.propertyGridTab.caption;
  };

  const button = new SurveyCreatorCore.MenuButton({
    id: "svc-copilot",
    tooltip: "Copilot",
    iconName: "icon-copilot",
    iconSize: "auto",
    pressed: false,
    action: () => (button.active ? close() : open())
  });

  // Sits in the bottom group of the tab strip
  const bottomToolbar = designer.tabControlModel.bottomToolbar;
  bottomToolbar.setItems([button].concat(bottomToolbar.actions));

  // Selecting an element on the design surface normally forces the property
  // grid page; keep the copilot open so the user can prompt while editing.
  const setPropertyGridIsActivePage = designer.setPropertyGridIsActivePage.bind(designer);
  designer.setPropertyGridIsActivePage = function () {
    if (!isOpen()) setPropertyGridIsActivePage();
  };

  // The category buttons are rebuilt (and the header re-titled) on every
  // selection change; restore our header and make the buttons leave the
  // copilot page before switching the property grid category.
  const updateTabControlActions = designer.updateTabControlActions.bind(designer);
  designer.updateTabControlActions = function () {
    updateTabControlActions();
    if (isOpen()) setHeader();

    designer.tabControlModel.topToolbar.actions.forEach(item => {
      const action = item.action;
      item.action = function () {
        if (isOpen()) close(designer.propertyGridTab.id);
        return action.apply(this, arguments);
      };
    });
  };

  return store;
}
