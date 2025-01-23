const extraInputs = [
    {"type":"text","name":"id","title":"ID","inputType":"number"},
    {"type":"text","name":"review","title":"Review"},
];

const surveyJson = JSON.parse(`{!! json_encode($form->content) !!}`);
surveyJson.pages[0].elements = extraInputs.concat(surveyJson.pages[0].elements); // Ensure 'id' is the first element

const surveyResults = JSON.parse(`{!! json_encode($submissions) !!}`);

const survey = new Survey.Model(surveyJson);

// Define the column order explicitly
const tabulatorColumns = [
    { title: "ID", field: "id" }, // Set 'id' as the first column
    { title: "Review", field: "review"},
    ...survey.pages[0].elements.map((el) => ({
        title: el.title,
        field: el.name,
        width: 150,
    })),
];

const surveyDataTable = new SurveyAnalyticsTabulator.Tabulator(
    survey,
    surveyResults,
    {
        columns: tabulatorColumns, // Pass the reordered columns to Tabulator
    }
);

document.addEventListener("DOMContentLoaded", function() {
    surveyDataTable.render(document.getElementById("surveyDataTable"));

    // onclick event listener, .tabulator-cell
    /*document.getElementById("surveyDataTable").addEventListener("click", function(event) {
        const target = event.target;
        if (target.classList.contains("tabulator-cell")) {
            const row = target.closest(".tabulator-row");
            console.log("Row ID:", row.getAttribute("tabulator-row"));
        }
    });*/

    // [tabulator-field="id"]')
    document.getElementById("surveyDataTable").addEventListener("click", function(event) {
        const target = event.target;
        if (target.hasAttribute("tabulator-field")) {
            // get value of the tabulator-field attribute
            const field = target.getAttribute("tabulator-field");
            if(field === "id"){
                let value = target.getAttribute("title");
                location.href = `@route('collections.show', ':id')`.replace(':id', value);
                return;
            }

            if(field === "review"){
                // get the title value of the sibling element with tabulator-field="id"
                let id = target.closest(".tabulator-row").querySelector('[tabulator-field="id"]').getAttribute("title");
                location.href = `@route('collections.show', ':id')`.replace(':id', id);
                return;
            }
        }
    });

    document.getElementById('purgeFormSubmissions').addEventListener('click', function() {
        Swal.fire({
            title: 'Are you sure?',
            text: "This action will delete all submissions for this form. This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete all data!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `@route('collections.clear', $form->id)`;
            }
        });
    });
});