@extends('layouts.form.main')
@style('/vendor/surveyjs/survey-creator-core.min.css','src')

@section('content')
    <div id="surveyCreator" style="height: 100vh;"></div>
@endsection()

@script('/vendor/surveyjs/surveyjs-widgets.min.js','src')
@script('/vendor/surveyjs/survey-creator-core.min.js','src')
@script('/vendor/surveyjs/dev/survey-creator-js.js','src')

@script('/vendor/ace/ace.min.js','src')
@script('/vendor/ace/ext-language_tools.js','src')
@script('/vendor/ace/ext-searchbox.min.js','src')
@script('app.forms.scripts.build')