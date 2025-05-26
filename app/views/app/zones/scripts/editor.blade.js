let sheet;
let spreadsheetTimeoutId = null;
sheet = new x_spreadsheet("#xspreadsheet", { 
    showToolbar: false, 
    showGrid: true,
    row : {
        len: 1000,
        height: 25
    },
});

// sheet.loadData(JSON.parse(zoneDataContent));
sheet.loadData(zoneDataContent);

async function requestSaveSheetData() {

    let loader = document.getElementById("sheetSaveLoader");
    loader.style.display = "block";

    var sheetData = await sheet.getData();
    var parsedData = await reformatXSpreadsheetData(sheetData);

    $.ajax({
        url: "@route('zones.update', $zone->id)",
        method: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            data: JSON.stringify(sheetData),
            content: JSON.stringify(parsedData)
        },
        success: function(response) {},
        error: function(error) {
            toast.error({ message: error.responseJSON?.message ?? "An error occurred while saving data" });
        },
        complete: function() {
            loader.style.display = "none";
        }
    });
}

// Listen to sheet changes with debounce
sheet.change(() => {
    if (spreadsheetTimeoutId) {
        clearTimeout(spreadsheetTimeoutId);
    }
    spreadsheetTimeoutId = setTimeout(() => {
        requestSaveSheetData();
        spreadsheetTimeoutId = null;
    }, 5000);
});

// Listen to Ctrl+S / Cmd+S for immediate save
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
        e.preventDefault();
        if (spreadsheetTimeoutId) {
            clearTimeout(spreadsheetTimeoutId);
            spreadsheetTimeoutId = null;
        }
        requestSaveSheetData();
    }
});

async function reformatXSpreadsheetData(sheets) {
  const result = [];

  for (const sheetKey in sheets) {
    const sheet = sheets[sheetKey];
    const sheetName = sheet.name || sheetKey;
    const rows = sheet.rows || {};

    const headerRow = rows[0]?.cells || {};
    const headers = [];

    // Build headers array from header row (row 0)
    for (const colKey in headerRow) {
      headers[colKey] = headerRow[colKey].text || `Column ${colKey}`;
    }

    const sheetData = [];

    for (const rowIndex in rows) {
      if (rowIndex == 0) continue; // Skip header

      const row = rows[rowIndex];
      const rowCells = row?.cells || {};
      const rowData = {};

      for (const colKey in headers) {
        const key = headers[colKey];
        const cell = rowCells[colKey];
        rowData[key] = cell ? cell.text : null;
      }

      sheetData.push(rowData);
    }

    result.push({
      [sheetName]: sheetData
    });
  }

  return result;
}