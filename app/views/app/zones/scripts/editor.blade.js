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

sheet.loadData(JSON.parse(zoneDataContent));

sheet.change((data) => {
    if (!spreadsheetTimeoutId) {
        spreadsheetTimeoutId = setTimeout( async () => {
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
                    spreadsheetTimeoutId = null;
                }
            });
        }, 5000);
    }
});

async function reformatXSpreadsheetData(data) {
    if (!data || !Array.isArray(data)) {
        throw new Error("Invalid x-spreadsheet data");
    }

    const formattedData = {};
    let rowDatas = [];

    data.forEach(sheet => {
        if (sheet.name && sheet.rows) {
            const headers = [];
            const rows = [];

            // Extract column headers from the first row (assumed to be at index 0)
            const firstRow = sheet.rows[0];
            Object.entries(firstRow.cells || {}).forEach(([colIndex, cell]) => {
                headers.push(cell.text || `Column${colIndex}`);
            });

            // Map rows to column-based data using the headers
            Object.values(sheet.rows).forEach((row, rowIndex) => {
                // Skip the first row since it's used as headers
                if (rowIndex === 0) return;

                const rowData = {};
                Object.entries(row.cells || {}).forEach(([colIndex, cell]) => {
                    // rowData[headers[colIndex]] = cell.text || "";
                    rowDatas.push({[headers[colIndex]]: cell.text || ""});
                });
                rows.push(rowData);
            });

            formattedData[sheet.name] = rows;
        }
    });

    return formattedData;
}