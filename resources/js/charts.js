/**
 * Charts for the whole app (ApexCharts), loaded by <x-chart> on the pages that have one. Each chart is a
 * <div data-pc-chart> holding its ApexCharts options as JSON (<script type="application/json">); they're drawn on the
 * app's look (font, brand colour, muted axes and grid, no toolbar) and redrawn when light / dark mode changes.
 * Page code can use window.ApexCharts, and window.pcCharts.render(el) for a chart added later.
 */
import ApexCharts from "apexcharts";

/** A CSS custom property of the page, e.g. token('--color-pc-brand'). */
function token(name) {
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
}

/** Deep-merges plain objects; arrays and other values from `override` replace those in `base`. */
function merge(base, override) {
    const out = { ...base };
    for (const [key, value] of Object.entries(override || {})) {
        const plain = value && typeof value === "object" && !Array.isArray(value);
        out[key] = plain && base[key] && typeof base[key] === "object" ? merge(base[key], value) : value;
    }
    return out;
}

/** The app's look, read from its tokens so it follows light / dark mode. */
function defaults() {
    const muted = token("--color-pc-muted");
    const border = token("--color-pc-border-soft");

    return {
        chart: {
            fontFamily: getComputedStyle(document.body).fontFamily,
            foreColor: muted,
            background: "transparent",
            toolbar: { show: false },
            zoom: { enabled: false },
            animations: { enabled: !window.matchMedia("(prefers-reduced-motion: reduce)").matches },
        },
        colors: [token("--color-pc-brand")],
        theme: { mode: document.documentElement.dataset.pcTheme === "dark" ? "dark" : "light" },
        grid: { borderColor: border, strokeDashArray: 3, padding: { left: 4, right: 8 } },
        xaxis: { axisBorder: { color: border }, axisTicks: { color: border } },
        dataLabels: { enabled: false },
        legend: { show: false },
        tooltip: { theme: document.documentElement.dataset.pcTheme === "dark" ? "dark" : "light" },
        states: { active: { filter: { type: "none" } } },
    };
}

const charts = new Map();

function render(el) {
    charts.get(el)?.destroy();
    const options = JSON.parse(el.querySelector("script[type='application/json']")?.textContent || "{}");
    const host = el.querySelector("[data-pc-chart-canvas]") || el;
    const chart = new ApexCharts(host, merge(defaults(), options));
    chart.render();
    charts.set(el, chart);
}

function renderAll() {
    document.querySelectorAll("[data-pc-chart]").forEach(render);
}

window.ApexCharts = ApexCharts;
window.pcCharts = { render, renderAll };

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", renderAll);
} else {
    renderAll();
}
document.addEventListener("pc:theme", renderAll);
