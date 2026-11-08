@extends('layouts.app.main')

@section('content')
    <div class="content pb-0 px-0" style="padding-top: var(--phoenix-navbar-top-height);">
        <div id="xspreadsheetWrap" class="w-100" style="height: calc(100vh - var(--phoenix-navbar-top-height)); overflow: hidden; 
            border-left: 0.5px dashed var(--phoenix-border-color);">
            <div id="xspreadsheet" class="w-100"></div>
        </div>
    </div>

    <div id="sheetSaveLoader" data-bs-toggle="tooltip" data-bs-placement="left"
        title="{{ __('Saving...') }}"
         style="
            position: absolute;
            bottom: 3px;
            left: 20px;
            z-index: 10;
            display: none;
        ">
        <i class="fa fa-spinner fa-spin"></i>
    </div>

    <script>
        const zoneDataContent = @json($zone->sheet);
    </script>
@endsection

@style('https://unpkg.com/x-data-spreadsheet@1.1.5/dist/xspreadsheet.css','src')
@script('https://unpkg.com/x-data-spreadsheet@1.1.5/dist/xspreadsheet.js','src')

@style('app.zones.styles.editor')
@script('app.zones.scripts.editor')