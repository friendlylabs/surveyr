<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $report->title }}</title>
    
    <link rel="stylesheet" href="/vendor/surveyjs/survey-core.min.css">
    <link rel="stylesheet" href="/vendor/surveyjs/survey.analytics.min.css">
    
    <style>
       @include('app.reports.styles.public') ;
    </style>
</head>
<body>

    
    <div class="content">
        <div class="report-detail">
            <h3>{{ $report->title }}</h3>
            <p>{{ $report->description }}</p>
        </div>
        <div id="reportContainer" class="doc-layout"></div>
    </div>

    <div id="surveyVizPanel" class="py-0 d-none"></div>
    
    <script src="/vendor/surveyjs/survey.core.min.js"></script>
    <script src="/vendor/surveyjs/survey-js-ui.min.js"></script>
    <script src="/vendor/surveyjs/plotly.min.js"></script>
    <script src="/vendor/surveyjs/survey.analytics.min.js"></script>
    <script>
        @include('app.reports.scripts.public') 
    </script>
</body>
</html>