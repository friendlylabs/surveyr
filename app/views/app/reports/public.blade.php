<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $report->title }}</title>

    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin="">
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="/assets/fonts/inter/inter.css" rel="stylesheet"/>
    <link rel="stylesheet" href="/vendor/surveyjs/survey-core.min.css">
    <link rel="stylesheet" href="/vendor/surveyjs/survey.analytics.min.css">

    <style>
        @include('app.reports.styles.report')

        /* stand-alone page: no Phoenix theme, so the tokens are set here */
        :root {
            --rp-bg: #f5f7fa; --rp-panel: #ffffff; --rp-raise: #eff2f6; --rp-line: #cbd0dd;
            --rp-text: #141824; --rp-text2: #31374a; --rp-muted: #6e7891; --rp-faint: #8a94ad;
            --rp-accent: #19b394; --rp-accent-fg: #ffffff; --rp-accent-soft: rgba(25, 179, 148, .12);
            color-scheme: light dark;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --rp-bg: #0f111a; --rp-panel: #141824; --rp-raise: #222834; --rp-line: #373e53;
                --rp-text: #eff2f6; --rp-text2: #b6bdcc; --rp-muted: #949db5; --rp-faint: #8a94ad;
                --rp-accent: #19b394; --rp-accent-soft: rgba(25, 179, 148, .16);
            }
        }

        html, body { margin: 0; padding: 0; background: var(--rp-bg); color: var(--rp-text); scroll-behavior: smooth; }
        body { font-family: "Inter var", "Nunito Sans", system-ui, sans-serif; font-size: 14px; -webkit-font-smoothing: antialiased; }
        a { color: var(--rp-accent); text-decoration: none; }
        .rp-page { max-width: 1240px; margin: 0 auto; padding: 24px 22px 48px; }
        .rp-topbar { display: flex; align-items: center; gap: 10px; margin-bottom: 18px; border-bottom: 1px solid var(--rp-line); }
        .rp-topbar .rp-brand { display: flex; align-items: center; gap: 8px; font-weight: 800; font-size: 13px; color: var(--rp-muted); }
        .rp-topbar .rp-brand img { height: 26px; }
        .rp-topbar .rp-print { margin-left: auto; padding: 6px 11px; border-radius: 7px; border: 1px solid var(--rp-line); background: var(--rp-panel); color: var(--rp-text2); font: inherit; font-size: 12px; font-weight: 700; cursor: pointer; }
        .rp-topbar .rp-print:hover { border-color: var(--rp-accent); color: var(--rp-accent); }
        .rp-toc { top: 16px; }
        .rp-section { scroll-margin-top: 16px; }
        @media print { .rp-topbar { display: none; } .rp-page { padding: 0; } }
    </style>
</head>
<body>
    <div class="rp-page">
        <div class="rp-topbar rp-no-print">
            <span class="rp-brand">
                <img src="/assets/images/brand/logo.png" alt="" onerror="this.remove()">
            </span>
            <button class="rp-print" onclick="window.print()">Print</button>
        </div>

        <div id="reportHost" data-empty-html="<div class='rp-att-notice'>This report has not been published yet.</div>"></div>
    </div>

    <script src="/vendor/surveyjs/survey.core.min.js"></script>
    <script src="/vendor/surveyjs/survey-js-ui.min.js"></script>
    <script src="/vendor/surveyjs/plotly.min.js"></script>
    <script src="/vendor/surveyjs/survey.analytics.min.js"></script>
    <script src="/vendor/surveyql/surveyql.min.js"></script>
    <script>
        @include('app.collections.scripts.viz-engine')
        @include('app.reports.scripts.core')
        @include('app.reports.scripts.viz')
        @include('app.reports.scripts.query')
        @include('app.reports.scripts.render')
        @include('app.reports.scripts.view')
    </script>
</body>
</html>
