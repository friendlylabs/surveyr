/**
 * Dashboard: submissions over time.
 *
 * The server hands over a { 'YYYY-MM-DD': count } map for the last year;
 * the chart fills the gaps and re-buckets it for the selected range
 * (daily for 30 / 90 days, monthly for 12 months).
 */
(function (global) {
    const chartDom = document.getElementById('submissionsChart');
    if (!chartDom || !global.echarts) return;

    const daily = global.dashboardSeries || {};
    const chart = echarts.init(chartDom);
    let range = 30;

    const pad = n => String(n).padStart(2, '0');
    const key = d => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    /** [{label, value, from, to}] for the range, oldest first, zero-filled */
    function buckets(days) {
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (days <= 90) {
            const out = [];
            for (let i = days - 1; i >= 0; i--) {
                const d = new Date(today);
                d.setDate(today.getDate() - i);
                out.push({ label: MONTHS[d.getMonth()] + ' ' + d.getDate(), value: daily[key(d)] || 0, date: d });
            }
            return out;
        }

        // last 12 calendar months, current month included
        const out = [];
        for (let i = 11; i >= 0; i--) {
            const first = new Date(today.getFullYear(), today.getMonth() - i, 1);
            const prefix = first.getFullYear() + '-' + pad(first.getMonth() + 1) + '-';
            const value = Object.keys(daily).reduce((n, k) => n + (k.indexOf(prefix) === 0 ? daily[k] : 0), 0);
            out.push({ label: MONTHS[first.getMonth()] + (first.getMonth() === 0 || i === 11 ? ' ' + String(first.getFullYear()).slice(2) : ''), value, date: first });
        }
        return out;
    }

    const tone = () => {
        const get = name => getComputedStyle(document.documentElement).getPropertyValue('--phoenix-' + name).trim();
        const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        return {
            dark,
            // primary hue, stepped down on the dark surface to stay in the lightness band
            line: dark ? '#16a085' : (get('primary') || '#19b394'),
            text: get('body-color') || (dark ? '#9fa6bc' : '#31374a'),
            muted: get('tertiary-color') || (dark ? '#8a94ad' : '#525b75'),
            grid: get('border-color') || (dark ? '#373e53' : '#cbd0dd'),
            surface: get('emphasis-bg') || (dark ? '#141824' : '#ffffff'),
        };
    };

    function render() {
        const t = tone();
        const data = buckets(range);
        const values = data.map(b => b.value);
        const unit = range > 90 ? 'month' : 'day';

        chart.setOption({
            animationDuration: 400,
            grid: { left: 8, right: 56, top: 20, bottom: 8, containLabel: true },
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'line', lineStyle: { color: t.muted, width: 1, type: 'solid' } },
                backgroundColor: t.surface,
                borderColor: t.grid,
                borderWidth: 1,
                padding: [8, 12],
                textStyle: { color: t.text, fontSize: 12 },
                formatter: params => {
                    const p = params[0];
                    const b = data[p.dataIndex];
                    const when = unit === 'month'
                        ? MONTHS[b.date.getMonth()] + ' ' + b.date.getFullYear()
                        : b.date.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
                    return '<div style="font-size:11px;color:' + t.muted + '">' + when + '</div>'
                        + '<div><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:' + t.line + ';margin-right:6px"></span>'
                        + '<strong>' + p.value.toLocaleString() + '</strong> submission' + (p.value === 1 ? '' : 's') + '</div>';
                },
            },
            xAxis: {
                type: 'category',
                boundaryGap: false,
                data: data.map(b => b.label),
                axisLine: { lineStyle: { color: t.grid } },
                axisTick: { show: false },
                axisLabel: { color: t.muted, fontSize: 11, hideOverlap: true, margin: 12 },
            },
            yAxis: {
                type: 'value',
                min: 0,
                minInterval: 1,
                splitNumber: 4,
                axisLabel: { color: t.muted, fontSize: 11, formatter: v => v.toLocaleString() },
                splitLine: { lineStyle: { color: t.grid, width: 1, type: 'solid' } },
            },
            series: [{
                name: 'Submissions',
                type: 'line',
                data: values,
                lineStyle: { width: 2, color: t.line, join: 'round', cap: 'round' },
                itemStyle: { color: t.line, borderColor: t.surface, borderWidth: 2 },
                areaStyle: { color: t.line, opacity: 0.1 },
                showSymbol: false,
                symbol: 'circle',
                symbolSize: 8,
                emphasis: { scale: false, itemStyle: { color: t.line } },
                // the one direct label: the latest bucket
                endLabel: {
                    show: true,
                    formatter: p => p.value.toLocaleString(),
                    color: t.text,
                    fontSize: 12,
                    fontWeight: 600,
                    offset: [8, 0],
                },
            }],
        }, true);
    }

    render();

    document.querySelectorAll('.dash-range [data-range]').forEach(btn => {
        btn.addEventListener('click', () => {
            range = parseInt(btn.dataset.range, 10);
            document.querySelectorAll('.dash-range [data-range]').forEach(b => b.classList.toggle('active', b === btn));
            render();
        });
    });

    // Phoenix fires this on the body when the theme toggle is used
    document.body.addEventListener('clickControl', e => {
        if (e.detail && e.detail.control === 'phoenixTheme') setTimeout(render, 50);
    });
    global.addEventListener('resize', () => chart.resize());
    const railToggle = document.querySelector('.navbar-vertical-toggle');
    if (railToggle) railToggle.addEventListener('navbar.vertical.toggle', () => chart.resize());

})(window);
