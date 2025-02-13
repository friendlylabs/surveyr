/*var editor = CodeMirror(document.getElementById('zoneDataEditor'), {
    value: JSON.stringify(zoneDataContent, null, 4),
    mode: 'json',
    lineNumbers: true,
    find: true,
    findNext: true,
    findPrev: true,
    replace: true,
    replaceAll: true
});*/

// Initialize table
var table = new Tabulator("#zoneDataTable", {
    data: zoneDataContent,
    history: true,
    autoColumns: true,
    movableColumns: true, // Allow column rearrangement
    autoColumnsDefinitions: function (definitions) {
        definitions.forEach((column) => {
            column.editor = "input"; // Set every column to use input editor
        });
        return definitions;
    }
});

require.config({
    paths: { 'vs': 'https://cdn.jsdelivr.net/npm/monaco-editor@0.38.0/min/vs' }
});

let editor;
require(['vs/editor/editor.main'], function() {
    editor = monaco.editor.create(document.getElementById('zoneDataEditor'), {
        value: JSON.stringify(zoneDataContent, null, 4),
        language: 'json',
        theme: 'vs-light'
    });
});

// Context menu handler
function openContextMenu(e, cell) {
    e.preventDefault(); // Prevent default right-click menu
    let menu = document.getElementById("tabulatorContextMenu");
    if (!menu) {
        menu = document.createElement("div");
        menu.id = "tabulatorContextMenu";
        menu.style.position = "absolute";
        menu.style.background = "#fff";
        menu.style.border = "1px solid #ccc";
        menu.style.padding = "5px";
        menu.style.boxShadow = "2px 2px 5px rgba(0,0,0,0.2)";
        menu.style.fontSize = "12px";
        menu.style.zIndex = 1000;
        document.body.appendChild(menu);
    }

    let columns = table.getColumns();
    let column = cell.getColumn();
    let row = cell.getRow();
    let columnIndex = columns.findIndex(function(col) {
        return col.getField() === column.getField();
    });
    let rowIndex = row.getPosition();

    menu.innerHTML = `
        <div class="tcm-menu" data-action="add-col-before">Add Column Before</div>
        <div class="tcm-menu" data-action="add-col-after">Add Column After</div>
        <div class="tcm-menu" data-action="delete-col">Delete Column</div>
        <!--div class="tcm-menu" data-action="rename-col">Rename Column</div-->
        <hr class="my-1">
        <div class="tcm-menu" data-action="add-row-before">Add Row Before</div>
        <div class="tcm-menu" data-action="add-row-after">Add Row After</div>
        <div class="tcm-menu" data-action="delete-row">Delete Row</div>
        <div class="tcm-menu" data-action="clear-row">Clear Row</div>
    `;

    menu.style.left = `${e.pageX}px`;
    menu.style.top = `${e.pageY}px`;
    menu.style.display = "block";

    menu.onclick = function (event) {
        let action = event.target.getAttribute("data-action");
        if (!action) return;

        if (action.includes("col")) {
            if (action === "add-col-before" || action === "add-col-after") {
                let columnName = prompt("Enter column name:");
                if (columnName) {
                    let columns = table.getColumns().map(col => col.getField());
                    let newColumns = [...columns];
                    let insertIndex = action === "add-col-before" ? columnIndex : columnIndex + 1;
                    newColumns.splice(insertIndex, 0, columnName);
                    table.setColumns(newColumns.map(field => ({ title: field, field, editor: "input" })));
                }
            } else if (action === "rename-col") {
                let newColumnName = prompt("Enter new column name:", column.getDefinition().title);
                if (newColumnName) {
                    var oldName = column.getField();
                    column.updateDefinition({ title: newColumnName });

                    // replace the tabulator tabulator-field="oldName" with the new name
                    document.querySelectorAll(`[tabulator-field="${oldName}"]`).forEach(function (el) {
                        el.setAttribute("tabulator-field", newColumnName);
                    });
                    
                    table.redraw();
                }
            } else if (action === "delete-col") {
                column.delete();
            } else if (action === "clear-col") {
                table.getData().forEach(row => row[column.getField()] = "");
                table.replaceData(table.getData());
            }
        } else if (action.includes("row")) {
            if (action === "add-row-before" || action === "add-row-after") {
                let newRow = {};
                table.getColumns().forEach(col => newRow[col.getField()] = "");
                if (action === "add-row-before") {
                    table.addRow(newRow, true, row.getElement());
                } else {
                    table.addRow(newRow, false, row.getElement());
                }
            } else if (action === "delete-row") {
                row.delete();
            } else if (action === "clear-row") {
                let rowData = row.getData();
                Object.keys(rowData).forEach(key => rowData[key] = "");
                row.update(rowData);
            }
        }
        menu.style.display = "none";
    };

}

// Hide context menu on click outside
document.addEventListener("click", () => {
    let menu = document.getElementById("tabulatorContextMenu");
    if (menu) menu.style.display = "none";
});

// Attach event listener
table.on("cellContext", openContextMenu);

document.getElementById("file-load-trigger").addEventListener("click", function(){
    table.import("xlsx", [".xlsx", ".csv", ".ods"], "buffer");
});

function switchEditor() {
    let codeEditor = document.getElementById("codeEditorContainer");
    let tableEditor = document.getElementById("tableEditorContainer");

    if (codeEditor.classList.contains("d-none")) {
        codeEditor.classList.remove("d-none");
        tableEditor.classList.add("d-none");
        editor.setValue(JSON.stringify(table.getData(), null, 2));
    } else {
        codeEditor.classList.add("d-none");
        tableEditor.classList.remove("d-none");
        
        // redraw the table with the new data
        table.setData(JSON.parse(getMonacoContent()));

    }
}

function getMonacoContent() {
    let content = editor.getValue();
    
    // If the content is valid JSON, clean it
    try {
        const jsonObject = JSON.parse(content);
        content = JSON.stringify(jsonObject, null, 2);
    } catch (e) {
        console.log("Invalid JSON");
    }

    return content;
}

/* save data to database */
document.getElementById("file-save-trigger").addEventListener("click", function(){
    
    // Get data from the container that is currently active
    let data = null;
    buttonState("#file-save-trigger", "loading");
    try{
        if (document.getElementById("codeEditorContainer").classList.contains("d-none")) {
            data = table.getData();
        } else {
            data = JSON.parse(editor.getValue());
        }
    }

    catch(e){
        toast.error({message: "Invalid Data"});
        buttonState("#file-save-trigger", "reset", '<i class="fa-solid fa-save"></i>');
        return;
    }

    $.ajax({
        url: "@route('zones.update', $zone->id)",
        type: "POST",
        data: { 
            _token: '{{ csrf_token() }}',
            content: data
        },
        success: function(response) {
            if (response.status) {
                toast.success({message: response.message});
            } else {
                toast.error({message: response.message ?? "An error occurred while saving data"});
            }
        },
        error: function(xhr, status, error) {
            toast.error({message: "An error occurred while saving data"});
        },
        complete: function() {
            buttonState("#file-save-trigger", "reset", '<i class="fa-solid fa-save"></i>');
        }
    });
});

// add column to table
document.getElementById("add-column").addEventListener("click", function(){
    let columnName = prompt("Enter column name:");
    if (columnName) {
        let columns = table.getColumns().map(col => col.getField());
        let newColumns = [...columns];
        newColumns.push(columnName);
        table.setColumns(newColumns.map(field => ({ title: field, field, editor: "input" })));
    }
});

// add row to table
document.getElementById("add-row").addEventListener("click", function(){
    let newRow = {};
    table.getColumns().forEach(col => newRow[col.getField()] = "");
    table.addRow(newRow);
});


document.addEventListener("DOMContentLoaded", function() {
    setTimeout(function() {
        switchEditor();
    }, 100);
});