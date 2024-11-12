@extends('layouts.form.main')

@section('content')
    <div id="surveyContainer" style="height: 100vh;"></div>
@endsection

@script('/vendor/surveyjs/themes/index.min.js','src')
@script('app.forms.scripts.show')