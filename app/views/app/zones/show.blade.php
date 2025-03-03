@extends('layouts.app.main')

@section('content')
    <div class="content pb-5" style="padding-top: 4rem; padding-left: 0; margin-left: -1rem;">
        <div class="ps-3" style="width: 100vw; height: calc(100vh - 75px); overflow: hidden;">
            <div id="xspreadsheet" class="w-100"></div>
        </div>
    </div>

    <script>
        const zoneDataContent = @json($zone->sheet);
    </script>
@endsection

@style('https://unpkg.com/x-data-spreadsheet@1.1.5/dist/xspreadsheet.css','src')
@script('https://unpkg.com/x-data-spreadsheet@1.1.5/dist/xspreadsheet.js','src')

@style('app.zones.styles.editor')
@script('app.zones.scripts.editor')