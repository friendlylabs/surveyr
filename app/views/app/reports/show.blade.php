@extends('layouts.app.main')

@style('/vendor/surveyjs/survey-core.min.css','src')
@style('/vendor/surveyjs/survey.analytics.min.css','src')

@section('content')
    <style>
        html,
        body {
            /* smooth scrolling */
            scroll-behavior: smooth;
            margin: 0;
            padding: 0;
        }

        .content {
            background:white !important;
            min-height: 100vh !important;
        }

        [data-bs-theme="dark"] .content{
            background: #141824 !important;
        }

        /* Base layout */
        .doc-layout {
            display: flex;
            gap: 2rem;
            margin: auto;
            padding: 2rem 0;
            max-width: 1440px;
        }

        /* Menu */
        .doc-menu {
            width: 220px;
            flex-shrink: 0;
            position: sticky;
            top: 1rem;
            align-self: flex-start;
        }

        .doc-menu-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .doc-menu-list a {
            display: block;
            padding: 0.5rem 0;
            text-decoration: none;
            font-weight: 700;
        }

        .doc-menu-list a:hover {
            font-weight: bold;
        }

        /* Content */
        .doc-content {
            flex: 1;
        }

        .report-row {
            position: relative;
            display: flex;
            gap: 2rem;
            align-items: flex-start;
            margin-bottom: 3rem;
        }

        /* Sticky .report-attachements */
        .report-attachements {
            flex: 1;
            min-width: 250px;
            border-radius: 6px;
            overflow-x: auto;

            position: sticky;
            top: 1rem;
            align-self: start;
            /* Important: don't stretch */
            max-height: fit-content;
            overflow: hidden;
            width: 100%;
        }

        .report-description {
            flex: 1.5;
            min-width: 250px;
        }

        .sa-question {
            display: inline !important;
        }

        .sa-question-layouted{
            position: relative;
        }

        /* Responsive styles */
        @media (max-width: 1023px) {
            .doc-layout {
                flex-direction: column;
            }

            .report-row {
                flex-direction: column;
            }
        }

        @media (max-width: 1224px) {

            /* collapse the menu on smaller screens */
            .doc-menu {
                display: none;
            }
        }

        @media print {
            .navbar,
            .doc-menu,
            .sa-visualizer__toolbar{
                display: none !important;
            }

            .content {
                padding: 0 !important;
            }

            .report-row {
                margin-bottom: 0 !important;
            }

            .report-detail {
                display: block !important;
                margin-bottom: 1rem;
            }

            .edit-report-btn {
                display: none !important;
            }
        }
    </style>
    <div class="content">
        <div class="report-detail d-none text-center">
            <h3 class="fs-7">{{ $report->title }}</h3>
            <p class="text-muted">{{ $report->description }}</p>
        </div>
        <div id="reportContainer" class="doc-layout"></div>
    </div>

    <!-- edit button -->
    @if($accessRole !== 'viewer')
        <div class="position-fixed end-0 bottom-0 p-3 edit-report-btn">
            <a href="@route('reports.edit', $report->id)" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-pencil me-2"></i>
                Edit Report
            </a>
        </div>
    @endif
    
    <div id="surveyVizPanel" class="py-0 d-none"></div>
    <script>

        document.addEventListener('DOMContentLoaded', function () {
            // Initialize the report attachments
            
        });

    
    </script>
        
@endsection

@script('/vendor/surveyjs/survey.core.min.js','src')
@script('/vendor/surveyjs/survey-js-ui.min.js','src')
@script('/vendor/surveyjs/plotly.min.js','src')
@script('/vendor/surveyjs/survey.analytics.min.js','src')
@script('app.reports.scripts.report')