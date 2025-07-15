const filterBuilder = (() => {
  const surveyQuestions = survey.getAllQuestions()

  const ruleContainer = document.getElementById("filterRuleContainer");
  let ruleIndex = 0;

  // Fixed filters data
  const reviewTypes = @json($form->reviews);
  
  // Create fixed filters section
  function createFixedFilters() {
    const container = document.getElementById("fixedFilterContainer");
    if (!container) return;
    
    const fixedFiltersHTML = `
      <div class="fixed-filters-section mb-4 p-3">
        <div class="row g-3">
          <div class="col-md-4 col-12">
            <label class="form-label">From Date</label>
            <input type="date" id="fromDate" class="form-control" />
          </div>
          <div class="col-md-4 col-12">
            <label class="form-label">To Date</label>
            <input type="date" id="toDate" class="form-control" />
          </div>
          <div class="col-md-4 col-12">
            <label class="form-label">Review Type</label>
            <select id="reviewType" class="form-select" multiple data-custom-select="true" data-select-search="true" data-select-placeholder="Select review types...">
              ${reviewTypes.map(review => `<option value="${review.value || review}">${review.text || review}</option>`).join('')}
            </select>
          </div>
        </div>
      </div>
    `;
    
    container.innerHTML = fixedFiltersHTML;
    
    // Initialize CustomSelect for review type and Flatpickr for date inputs
    setTimeout(() => {
      if (window.CustomSelect) {
        new CustomSelect();
      }
      
      // Flatpickr for date inputs
      const dateInputs = container.querySelectorAll('input[type="date"]');
      dateInputs.forEach(input => {
        input.placeholder = "Select date";
        flatpickr(input, {
          dateFormat: "Y-m-d",
          allowInput: true,
          altInput: true,
          altFormat: "F j, Y",
        });
      });
    }, 100);
  }

  const filterTypes = {
    text: ["contains", "startsWith", "endsWith"],
    fuzzy: ["contains"],
    numeric: ["equals", "notEquals", "gt", "lt", "gte", "lte", "range"], // moved 'range' here
    check: ["empty", "notEmpty"],
    choice: ["equals", "notEquals", "contains"]
  };

  // User-friendly display names
  const typeDisplayNames = {
    text: "Text",
    fuzzy: "Fuzzy",
    numeric: "Numeric", 
    check: "Check",
    choice: "Choice"
  };

  const operatorDisplayNames = {
    contains: "Contains",
    startsWith: "Starts With", 
    endsWith: "Ends With",
    equals: "Equals",
    notEquals: "Not Equals",
    gt: "Greater Than",
    lt: "Less Than", 
    gte: "Greater or Equal",
    lte: "Less or Equal",
    range: "Between",
    empty: "Is Empty",
    notEmpty: "Is Not Empty"
  };

  function createRuleRow(index, preset = {}) {
    const row = document.createElement("div");
    row.className = "ruleCard p-3 py-5 mb-2 position-relative";
    row.dataset.index = index;

    const questionOptions = surveyQuestions.map(q => `<option value="${q.name}"${preset.question === q.name ? " selected" : ""}>${q.title || q.name}</option>`).join("");

    row.innerHTML = `
      <button type="button" class="btn position-absolute end-0 top-0 m-2" aria-label="Close" onclick="this.parentElement.remove()">
        <i class="fa fa-trash-alt text-danger"></i>
      </button>
      <div class="row g-2">
        <div class="col-md-3">
          <select class="form-select question-select">
            <option value="">Select question...</option>
            ${questionOptions}
          </select>
        </div>
        <div class="col-md-2">
          <select class="form-select filter-type">
            <option value="">Type...</option>
            ${Object.keys(filterTypes).map(type => `<option value="${type}"${preset.type === type ? " selected" : ""}>${typeDisplayNames[type]}</option>`).join("")}
          </select>
        </div>
        <div class="col-md-2">
          <select class="form-select operator-select">
            <option value="">Operator...</option>
          </select>
        </div>
        <div class="col-md-4 value-field"></div>
      </div>
    `;

    const questionSelect = row.querySelector(".question-select");
    const typeSelect = row.querySelector(".filter-type");
    const operatorSelect = row.querySelector(".operator-select");
    const valueField = row.querySelector(".value-field");

    function populateOperators() {
      operatorSelect.innerHTML = `<option value="">Operator...</option>`;
      (filterTypes[typeSelect.value] || []).forEach(op => {
        const selected = preset.operator === op ? " selected" : "";
        const displayName = operatorDisplayNames[op] || op;
        operatorSelect.innerHTML += `<option value="${op}"${selected}>${displayName}</option>`;
      });
    }

    function populateValueField() {
      valueField.innerHTML = "";
      const q = surveyQuestions.find(q => q.name === questionSelect.value);

      if (typeSelect.value === "choice") {
        const select = document.createElement("select");
        select.className = "form-select value-input";
        select.multiple = true;
        select.setAttribute("data-custom-select", "true");
        select.setAttribute("data-select-search", "true");
        select.setAttribute("data-select-placeholder", "Select options...");

        if (q?.choices && q.choices.length > 0) {
          q.choices.forEach(opt => {
            const val = typeof opt === "string" ? opt : opt.value;
            const txt = typeof opt === "string" ? opt : opt.text || opt.value;
            const selected = Array.isArray(preset.value) && preset.value.includes(val) ? " selected" : "";
            select.innerHTML += `<option value="${val}"${selected}>${txt}</option>`;
          });
          valueField.appendChild(select);
          
          // Initialize CustomSelect
          if (window.CustomSelect) {
            new CustomSelect();
          }
        } else if (q?.choicesByUrl) {
          // Use SurveyJS's built-in choicesFromUrl which handles loading automatically
          const choices = q.visibleChoices || [];
          choices.forEach(choice => {
            const val = choice.value;
            const txt = choice.text || choice.value;
            const selected = Array.isArray(preset.value) && preset.value.includes(val) ? " selected" : "";
            select.innerHTML += `<option value="${val}"${selected}>${txt}</option>`;
          });
          valueField.appendChild(select);
          
          // Initialize CustomSelect
          if (window.CustomSelect) {
            new CustomSelect();
          }
        }
      } else if (["text", "numeric"].includes(typeSelect.value)) {
        // If operator is 'range' and type is numeric, show min/max fields
        if (typeSelect.value === "numeric" && operatorSelect.value === "range") {
          valueField.innerHTML = `
            <div class="d-flex gap-2">
              <input type="number" class="form-control value-input-min" placeholder="Min" value="${preset.value?.min || ''}">
              <input type="number" class="form-control value-input-max" placeholder="Max" value="${preset.value?.max || ''}">
            </div>`;
        } else {
          valueField.innerHTML = `<input type="text" class="form-control value-input" value="${preset.value || ''}">`;
        }
      } else if (["fuzzy"].includes(typeSelect.value)) {
        valueField.innerHTML = `<input type="text" class="form-control value-input" value="${(preset.value || []).join(', ')}" placeholder="Comma-separated">`;
      }
    }

    operatorSelect.addEventListener("change", () => {
      populateValueField();
    });

    typeSelect.addEventListener("change", () => {
      populateOperators();
      populateValueField();
    });

    questionSelect.addEventListener("change", () => {
      if (typeSelect.value === "choice") populateValueField();
    });

    // Initial populate if preset exists
    if (preset.operator) populateOperators();
    if (preset.value !== undefined) populateValueField();
    setTimeout(() => {
      // Ensure CustomSelect is initialized after the value field is populated
      if (window.CustomSelect) {
        const customSelects = row.querySelectorAll("[data-custom-select]");
        if(customSelects.length > 0) {
          new CustomSelect();
        }
      }
    }, 100);

    return row;
  }

  function compileFilters() {
    // Collect fixed filters first
    const fixedFilters = {};
    
    const fromDate = document.getElementById("fromDate")?.value;
    const toDate = document.getElementById("toDate")?.value;
    const reviewTypeSelect = document.getElementById("reviewType");
    const reviewType = reviewTypeSelect ? Array.from(reviewTypeSelect.selectedOptions).map(o => o.value) : [];
    
    if (fromDate) fixedFilters.from_date = fromDate;
    if (toDate) fixedFilters.to_date = toDate;
    if (reviewType.length > 0) fixedFilters.review_type = reviewType;

    // Collect dynamic rules
    const rules = ruleContainer.querySelectorAll(".ruleCard");
    const dynamicFilters = {};

    rules.forEach(card => {
      const question = card.querySelector(".question-select").value;
      const type = card.querySelector(".filter-type").value;
      const operator = card.querySelector(".operator-select").value;
      let value;

      if (!question || !type || !operator) return;

      // If numeric and operator is range, collect min/max
      if (type === "numeric" && operator === "range") {
        value = {
          min: card.querySelector(".value-input-min")?.value,
          max: card.querySelector(".value-input-max")?.value
        };
      } else if (["fuzzy", "choice"].includes(type)) {
        if (type === "choice") {
          const selectElement = card.querySelector(".value-input");
          // The CustomSelect maintains the original select's selectedOptions
          value = Array.from(selectElement?.selectedOptions || []).map(o => o.value);
        } else if (type === "fuzzy") {
          const raw = card.querySelector(".value-input")?.value;
          value = raw.split(",").map(x => x.trim()).filter(Boolean);
        }
      } else if (type === "check") {
        value = null;
      } else {
        value = card.querySelector(".value-input")?.value;
      }

      if (!dynamicFilters[question]) dynamicFilters[question] = [];
      dynamicFilters[question].push({ type, operator, value });
    });

    // Combine fixed and dynamic filters
    const combinedFilters = { ...fixedFilters, ...dynamicFilters };

    return combinedFilters;
  }
  
  function collectFilters() {
    const combinedFilters = compileFilters();

    const encoded = btoa(encodeURIComponent(JSON.stringify(combinedFilters)));
    const url = new URL(window.location.href);
    url.searchParams.set("filters", encoded);
    window.location.href = url.toString();
  }

  function loadFiltersFromURL() {
    const params = new URLSearchParams(window.location.search);
    const encoded = params.get("filters");
    if (!encoded) return;

    try {
      const decoded = JSON.parse(decodeURIComponent(atob(encoded)));
      
      // Load fixed filters
      if (decoded.from_date) {
        const fromDateInput = document.getElementById("fromDate");
        if (fromDateInput) fromDateInput.value = decoded.from_date;
      }
      
      if (decoded.to_date) {
        const toDateInput = document.getElementById("toDate");
        if (toDateInput) toDateInput.value = decoded.to_date;
      }
      
      if (decoded.review_type && Array.isArray(decoded.review_type)) {
        const reviewTypeSelect = document.getElementById("reviewType");
        if (reviewTypeSelect) {
          decoded.review_type.forEach(value => {
            const option = reviewTypeSelect.querySelector(`option[value="${value}"]`);
            if (option) option.selected = true;
          });
          
          // Refresh CustomSelect if initialized
          setTimeout(() => {
            if (window.CustomSelect) {
              new CustomSelect();
            }
          }, 100);
        }
      }
      
      // Load dynamic filters (skip fixed filter keys)
      const fixedFilterKeys = ['from_date', 'to_date', 'review_type'];
      for (const [question, rules] of Object.entries(decoded)) {
        if (fixedFilterKeys.includes(question)) continue;
        
        if (Array.isArray(rules)) {
          rules.forEach(rule => {
            const row = createRuleRow(ruleIndex++, {
              question,
              ...rule
            });
            ruleContainer.appendChild(row);
          });
        }
      }
    } catch (e) {
      console.error("Failed to load saved filters", e);
    }
  }

  document.getElementById("addRuleBtn").addEventListener("click", () => {
    const rule = createRuleRow(ruleIndex++);
    ruleContainer.appendChild(rule);
  });

  document.getElementById("applyFiltersBtn").addEventListener("click", collectFilters);

  // Initialize fixed filters
  createFixedFilters();
  
  // Add dynamic rules section title
  /*if (ruleContainer) {
    const dynamicRulesTitle = document.createElement("h6");
    dynamicRulesTitle.className = "mb-3";
    dynamicRulesTitle.textContent = "Survey Question Filters";
    ruleContainer.parentNode.insertBefore(dynamicRulesTitle, ruleContainer);
  }*/
  
  // Load filters from URL after everything is set up
  loadFiltersFromURL();

  // Return public API
  const publicAPI = {
    compileFilters,
    loadFiltersFromURL,
    createRuleRow
  };

  // Also make it globally accessible
  window.filterBuilder = publicAPI;

  return publicAPI;
})();