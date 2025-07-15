@extends('layouts.form.main')
@style('/vendor/surveyjs/survey-creator-core.min.css','src')

@section('content')
    
    @if(_env('SURVEYJS_LICENSE_KEY'))
        <style>
            .svc-creator__banner { display: none !important; }
        </style>
    @endif

    <div id="surveyCreator" style="height: 100vh;"></div>

    <script>
        const initialData = '{{ $form->title }}';
        const placeholder = 'Descript or paste your form description here...';
    </script>

    <script type="importmap">
		{
			"imports": {
				"ckeditor5": "/vendor/ckeditor5/ckeditor5.js",
				"ckeditor5/": "/vendor/ckeditor5/"
			}
		}
	</script>
@endsection()

@style('app.forms.styles.generator')

@script('/vendor/surveyjs/survey-creator-core.min.js','src')
@script('/vendor/surveyjs/survey-creator-js.min.js','src')
@script('/vendor/surveyjs/surveyjs-widgets.min.js','src')

@script('/vendor/ace/ace.min.js','src')
@script('/vendor/ace/ext-language_tools.js','src')
@script('/vendor/ace/ext-searchbox.min.js','src')

@style('/vendor/ckeditor5/ckeditor5.css','src')

@script('app.forms.scripts.agent')
@script('app.forms.scripts.build')

@script('/vendor/ckeditor5/main.js','srcmodule')