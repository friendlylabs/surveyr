@extends('layouts.app.main')

@push('styles')
    <style>
        .tabulatorEditToolbar {
        }

        .btn-tabulator {
            padding-top: 2px;
            background-color: var(--phoenix-btn-bg);
            border: 1px solid #d1d1d1;
            border-radius: 5px;
        }

        .tcm-menu {
            padding: 5px 10px;
            cursor: pointer;
        }

        .tcm-menu:hover {
            background-color: #f1f1f1;
            border-radius: 4px;
        }

        .CodeMirror {
            height: calc(100vh - 200px) !important;
        }
    </style>
@endpush

@section('content')
    <div class="content pb-5">
        <div class="container-fluid mb-3">
            <div class="tabulatorEditToolbar">
                <button class="btn-tabulator" id="history-undo"
                    data-bs-toggle="tooltip" data-bs-placement="bottom" title="Undo">
                    <i class="fa-solid fa-undo"></i>
                </button>
                <button class="btn-tabulator" id="history-redo"
                    data-bs-toggle="tooltip" data-bs-placement="bottom" title="Redo">
                    <i class="fa-solid fa-redo"></i>
                </button>
                
                <!-- switch between code editor and table editor, fa-grid and fa-code -->
                <button class="btn-tabulator" id="switch-editor" onclick="switchEditor()"
                    data-bs-toggle="tooltip" data-bs-placement="bottom" title="Switch Editor">
                    <i class="fa-solid fa-code"></i>
                </button>
                <button class="btn-tabulator" id="file-save-trigger"
                    data-bs-toggle="tooltip" data-bs-placement="bottom" title="Save Data">
                    <i class="fa-solid fa-save"></i>
                </button>

                <!-- #add-column, #add-row -->
                <button class="btn-tabulator" id="add-column"
                    data-bs-toggle="tooltip" data-bs-placement="bottom" title="Add Column">
                    <i class="fa-solid fa-columns"></i>
                </button>
                <button class="btn-tabulator" id="add-row"
                    data-bs-toggle="tooltip" data-bs-placement="bottom" title="Add Row">
                    <i class="fa-solid fa-table"></i>
                </button>

                <button class="btn-tabulator" id="file-load-trigger">Import Data</button>
            </div>
        </div>

        <div class="container-fluid" id="codeEditorContainer">
            <div id="zoneDataEditor" style="height: calc(100vh - 200px);"></div>

        </div>

        <div class="container-fluid d-none" id="tableEditorContainer">
            <div id="zoneDataTable"></div>
        </div>

    </div>

    <script>
        const zoneDataContent = @json($zone->content);
    </script>
@endsection

@style('/vendor/codemirror/codemirror.min.css','src')
@script('/vendor/codemirror/codemirror.min.js','src')
@script('/vendor/codemirror/search.js','src')

@style('/vendor/codemirror/dialog.css','src')
@script('/vendor/codemirror/dialog.js','src')

@style('/vendor/tabulator/tabulator@6.min.css','src')
@script('/vendor/tabulator/tabulator@6.min.js','src')

@style('/vendor/monaco/editor.main.min.css','src')
@script('/vendor/monaco/loader.min.js','src')

@script('app.zones.scripts.show')
@script('app.zones.scripts.editor')