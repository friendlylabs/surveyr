class CustomSelect {
  constructor(selector = 'select[data-custom-select]') {
    this.selector = selector;
    this.init();
  }

  init() {
    const selects = document.querySelectorAll(this.selector);
    selects.forEach(select => {
      if (select.dataset.csInitialized) return;
      this.createCustomSelect(select);
      select.dataset.csInitialized = 'true';
    });
  }

  createCustomSelect(select) {
    const wrapper = document.createElement('div');
    wrapper.className = 'cs-wrapper';
    const isMultiple = select.multiple;
    const placeholder = select.dataset.selectPlaceholder || 'Select...';
    const enableSearch = select.dataset.selectSearch === 'true';

    const display = document.createElement('div');
    display.className = 'cs-display';
    display.textContent = placeholder;

    const dropdown = document.createElement('div');
    dropdown.className = 'cs-dropdown';

    if (enableSearch) {
      const search = document.createElement('input');
      search.type = 'text';
      search.className = 'cs-search border-0 mb-1';
      search.placeholder = 'Search...';
      search.addEventListener('input', () => {
        const val = search.value.toLowerCase();
        dropdown.querySelectorAll('.cs-option').forEach(opt => {
          opt.style.display = opt.textContent.toLowerCase().includes(val) ? '' : 'none';
        });
      });
      dropdown.appendChild(search);
    }

    const updateDisplay = () => {
      const selected = Array.from(select.selectedOptions).map(o => o.textContent);
      display.textContent = selected.length ? selected.join(', ') : placeholder;
    };

    select.querySelectorAll('option').forEach(opt => {
      const optDiv = document.createElement('div');
      optDiv.className = 'cs-option';
      optDiv.textContent = opt.textContent;
      optDiv.dataset.value = opt.value;

      if (opt.selected) optDiv.classList.add('selected');

      optDiv.addEventListener('click', () => {
        if (isMultiple) {
          opt.selected = !opt.selected;
          optDiv.classList.toggle('selected');
        } else {
          select.querySelectorAll('option').forEach(o => o.selected = false);
          dropdown.querySelectorAll('.cs-option').forEach(o => o.classList.remove('selected'));
          opt.selected = true;
          optDiv.classList.add('selected');
          dropdown.classList.remove('open');
        }

        select.dispatchEvent(new Event('change'));
        updateDisplay();
      });

      dropdown.appendChild(optDiv);
    });

    display.addEventListener('click', () => {
      dropdown.classList.toggle('open');
    });

    wrapper.appendChild(display);
    wrapper.appendChild(dropdown);
    select.style.display = 'none';
    select.insertAdjacentElement('afterend', wrapper);

    updateDisplay();

    document.addEventListener('click', (e) => {
      if (!wrapper.contains(e.target)) {
        dropdown.classList.remove('open');
      }
    });
  }

  refresh() {
    document.querySelectorAll(this.selector).forEach(select => {
      const wrapper = select.nextElementSibling;
      if (wrapper?.classList.contains('cs-wrapper')) {
        wrapper.remove();
        select.removeAttribute('data-cs-initialized');
      }
    });
    this.init();
  }
}

window.CustomSelect = CustomSelect;

const customSelect = new CustomSelect();
