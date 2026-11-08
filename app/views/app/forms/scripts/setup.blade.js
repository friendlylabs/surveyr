/**
 * Form overview page
 * --------------------------------------------------------------------------
 * Tabs (kept in ?tab=), the daily responses chart, the share modal's QR code,
 * the theme picker, and the saved filter list + editor of the Submissions tab.
 */
(function (global) {
    'use strict';

    const formFlags = @json($form->flagGroup?->flags ?? []);
    const formSurvey = new Survey.Model(@json($form->content) || {});

    /* ------------------------------------------------------------------ *
     |  Tabs                                                              |
     * ------------------------------------------------------------------ */

    const tabs = document.querySelectorAll('.fo-tabs [data-tab]');

    function showTab(name) {
        tabs.forEach(t => {
            const on = t.dataset.tab === name;
            t.classList.toggle('active', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        document.querySelectorAll('.fo-pane').forEach(p => { p.hidden = p.dataset.pane !== name; });

        const url = new URL(global.location.href);
        url.searchParams.set('tab', name);
        history.replaceState(null, '', url);

        if (name === 'overview' && chart) chart.resize();
    }

    tabs.forEach(t => t.addEventListener('click', () => showTab(t.dataset.tab)));

    // settings sub-nav: jump to the card, mark it current
    document.querySelectorAll('.fo-subnav a').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            document.querySelectorAll('.fo-subnav a').forEach(a => a.classList.toggle('active', a === link));
            document.querySelector(link.getAttribute('href'))?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    /* ------------------------------------------------------------------ *
     |  Daily responses                                                   |
     * ------------------------------------------------------------------ */

    const chartDom = document.getElementById('foDailyChart');
    const chart = chartDom && global.echarts ? echarts.init(chartDom) : null;
    const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    const tone = () => {
        const get = name => getComputedStyle(document.documentElement).getPropertyValue('--phoenix-' + name).trim();
        const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        return {
            bar: dark ? '#16a085' : (get('primary') || '#19b394'),
            line: dark ? '#e3e6ed' : '#31374a',
            text: get('body-color') || (dark ? '#9fa6bc' : '#31374a'),
            muted: get('tertiary-color') || (dark ? '#8a94ad' : '#525b75'),
            grid: get('border-color-translucent') || (dark ? '#373e53' : '#e3e6ed'),
            surface: get('emphasis-bg') || (dark ? '#141824' : '#ffffff'),
        };
    };

    /** the last 30 days, oldest first, zero-filled, with a trailing 7-day mean */
    function days() {
        const daily = global.formOverviewSeries || {};
        const pad = n => String(n).padStart(2, '0');
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const out = [];
        for (let i = 29; i >= 0; i--) {
            const d = new Date(today);
            d.setDate(today.getDate() - i);
            out.push({ date: d, value: daily[`${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`] || 0 });
        }
        out.forEach((b, i) => {
            const win = out.slice(Math.max(0, i - 6), i + 1);
            b.avg = Math.round(win.reduce((n, x) => n + x.value, 0) / win.length * 10) / 10;
        });
        return out;
    }

    function renderChart() {
        if (!chart) return;
        const t = tone();
        const data = days();

        chart.setOption({
            animationDuration: 400,
            grid: { left: 4, right: 8, top: 16, bottom: 4, containLabel: true },
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow', shadowStyle: { color: 'rgba(127,127,127,.08)' } },
                backgroundColor: t.surface,
                borderColor: t.grid,
                padding: [8, 12],
                textStyle: { color: t.text, fontSize: 12 },
                formatter: params => {
                    const b = data[params[0].dataIndex];
                    const when = b.date.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
                    return `<div style="font-size:11px;color:${t.muted}">${when}</div>`
                        + `<div><strong>${b.value.toLocaleString()}</strong> response${b.value === 1 ? '' : 's'}</div>`
                        + `<div style="font-size:11px;color:${t.muted}">7-day average ${b.avg}</div>`;
                },
            },
            xAxis: {
                type: 'category',
                data: data.map(b => `${MONTHS[b.date.getMonth()]} ${b.date.getDate()}`),
                axisLine: { lineStyle: { color: t.grid } },
                axisTick: { show: false },
                axisLabel: { color: t.muted, fontSize: 11, hideOverlap: true, margin: 10 },
            },
            yAxis: {
                type: 'value',
                min: 0,
                minInterval: 1,
                splitNumber: 4,
                axisLabel: { color: t.muted, fontSize: 11 },
                splitLine: { lineStyle: { color: t.grid } },
            },
            series: [
                {
                    name: 'Responses',
                    type: 'bar',
                    data: data.map(b => b.value),
                    barMaxWidth: 22,
                    itemStyle: { color: t.bar, opacity: 0.85, borderRadius: [3, 3, 0, 0] },
                    emphasis: { itemStyle: { opacity: 1 } },
                },
                {
                    name: '7-day average',
                    type: 'line',
                    data: data.map(b => b.avg),
                    smooth: 0.3,
                    showSymbol: false,
                    lineStyle: { width: 2, color: t.line, type: 'solid' },
                    itemStyle: { color: t.line },
                    z: 3,
                },
            ],
        }, true);
    }

    renderChart();
    document.body.addEventListener('clickControl', e => {
        if (e.detail && e.detail.control === 'phoenixTheme') setTimeout(renderChart, 50);
    });
    global.addEventListener('resize', () => chart && chart.resize());
    document.querySelector('.navbar-vertical-toggle')?.addEventListener('navbar.vertical.toggle', () => chart && chart.resize());

    /* ------------------------------------------------------------------ *
     |  Share: QR code, drawn the first time the modal opens               |
     * ------------------------------------------------------------------ */

    let qr = null;
    document.getElementById('shareFormModal')?.addEventListener('shown.bs.modal', () => {
        if (qr || !global.QRCode) return;
        qr = new QRCode(document.getElementById('qrcode'), { width: 132, height: 132, colorDark: '#1e6b5b', colorLight: '#ffffff' });
        qr.makeCode(document.getElementById('publicUrl').value);
    });

    global.downloadQRCode = function (name) {
        const img = document.querySelector('#qrcode img');
        if (!img || !img.src) return toast.error({ message: 'The QR code is not ready yet.' });
        const link = document.createElement('a');
        link.href = img.src;
        link.download = `${name}-qrcode.png`;
        document.body.appendChild(link);
        link.click();
        link.remove();
    };

    /* ------------------------------------------------------------------ *
     |  Theme picker                                                      |
     * ------------------------------------------------------------------ */

    document.querySelectorAll('.fo-theme').forEach(card => {
        card.addEventListener('click', () => {
            const previous = document.querySelector('.fo-theme.is-selected');
            const select = el => document.querySelectorAll('.fo-theme').forEach(c => {
                c.classList.toggle('is-selected', c === el);
                c.setAttribute('aria-pressed', c === el ? 'true' : 'false');
            });
            select(card);

            $.ajax({
                url: "{{ route('forms.setup.update', 'theme', $form->id) }}",
                type: 'POST',
                data: { _token: "{{ csrf_token() }}", theme: card.dataset.theme },
            }).then(response => {
                if (!response.status) throw new Error(response.message);
                toast.success({ message: response.message });
            }).catch(error => {
                select(previous);
                toast.error({ message: error.message || 'An error occurred' });
            });
        });
    });

    /* ------------------------------------------------------------------ *
     |  Saved filters: constraint chips                                    |
     * ------------------------------------------------------------------ */

    const OPS = {
        contains: 'contains', startsWith: 'starts with', endsWith: 'ends with', equals: 'is', notEquals: 'is not',
        gt: '>', lt: '<', gte: '≥', lte: '≤', range: 'between', empty: 'is empty', notEmpty: 'is filled',
    };
    const titles = Object.fromEntries(formSurvey.getAllQuestions().map(q => [q.name, q.title || q.name]));
    const flagLabel = key => key === '__none' ? 'Unflagged' : (formFlags.find(f => f.key === key)?.label || key);
    const day = s => new Date(s + 'T00:00:00').toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });

    function chips(filters) {
        const out = [];
        if (filters.from_date && filters.to_date) out.push(`${day(filters.from_date)} – ${day(filters.to_date)}`);
        else if (filters.from_date) out.push(`From ${day(filters.from_date)}`);
        else if (filters.to_date) out.push(`Until ${day(filters.to_date)}`);
        if (Array.isArray(filters.flag) && filters.flag.length) out.push(`Flag: ${filters.flag.map(flagLabel).join(', ')}`);

        Object.keys(filters).forEach(name => {
            if (['from_date', 'to_date', 'flag'].includes(name) || !Array.isArray(filters[name])) return;
            filters[name].forEach(rule => {
                let value = rule.value;
                if (rule.operator === 'range' && value) value = `${value.min || '…'}–${value.max || '…'}`;
                else if (Array.isArray(value)) value = value.join(', ');
                out.push(`${titles[name] || name} ${OPS[rule.operator] || rule.operator}${value != null && value !== '' ? ' ' + value : ''}`);
            });
        });
        return out;
    }

    document.querySelectorAll('[data-filter-chips]').forEach(box => {
        let filters = {};
        try { filters = JSON.parse(box.dataset.filterChips) || {}; } catch (e) { /* leave empty */ }
        box.innerHTML = chips(filters).map(c => `<span class="fo-chip">${FilterBuilder.esc(c)}</span>`).join('');
    });

    /* ------------------------------------------------------------------ *
     |  Saved filters: editor                                              |
     * ------------------------------------------------------------------ */

    global.FormFilterEditor = (function () {
        const offcanvasEl = document.getElementById('filterEditorOffcanvas');
        const form = document.getElementById('filterEditorForm');
        let builder = null;
        let editingId = null;

        function ensureBuilder() {
            if (builder) return builder;
            builder = FilterBuilder.create({
                survey: formSurvey,
                flags: formFlags,
                view: CollectionFilterView.create(document.getElementById('filterEditorRules')),
            });
            return builder;
        }

        /** Open empty for a new filter, or filled from {id, name, description, filters}. */
        function open(saved = null) {
            editingId = saved ? saved.id : null;
            form.elements.name.value = saved ? saved.name : '';
            form.elements.description.value = saved ? (saved.description || '') : '';
            ensureBuilder().load(saved && saved.filters && !Array.isArray(saved.filters) ? saved.filters : {});

            document.getElementById('filterEditorLabel').textContent = saved ? 'Edit filter' : 'New filter';
            document.getElementById('filterEditorSubmit').textContent = saved ? 'Save changes' : 'Save filter';
            bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl).show();
        }

        form.addEventListener('submit', e => {
            e.preventDefault();
            const button = document.getElementById('filterEditorSubmit');
            const label = button.innerHTML;
            buttonState($(button), 'loading');

            $.ajax({
                url: editingId ? form.dataset.update.replace(':id', editingId) : form.dataset.store,
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    name: form.elements.name.value,
                    description: form.elements.description.value,
                    filters: JSON.stringify(builder.compileFilters()),
                },
            }).then(response => {
                if (!response.status) return toast.error({ message: response.message || 'Could not save the filter' });
                toast.success({ message: response.message });
                if (response.redirect) setTimeout(() => { global.location.href = response.redirect; }, 600);
            }).catch(() => toast.error({ message: 'Could not save the filter' }))
              .always(() => buttonState($(button), 'reset', label));
        });

        return { open };
    })();

})(window);
