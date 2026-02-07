const reviewJson = @json($form->reviews) || []; // sample ["Pending", "Approved", "Rejected"]
const surveyJson = @json($form->content) || {};
const surveyResults = @json($submissions) || [];

if (surveyJson.length) {
    surveyJson.mode = "display";
}

const survey = new Survey.Model(surveyJson);

const questions = survey.getAllQuestions();
// traverse through questions and get the question.name
columns = [
    {
        id: "id",
        name: "ID",
        hidden: true
    },
    {
        id: "review",
        name: "Status",
        formatter: (cell, row) => {
            const currentValue = cell || '';

            return gridjs.html(
                `
                <div class="submission-action">
                    <button class="btn btn-sm" onclick="viewSubmissionCanvas(${row.cells[0].data}, this)"
                        data-bs-toggle="tooltip" data-bs-placement="bottom" title="View Submission">
                        <i class="fa fa-chevron-right text-light"></i>
                    </button>
                </div>
                
                <select class="review-select form-select" name="review" data-id="${row.cells[0].data}" onchange="changeSubmissionReviewStatus(this)">
                    ${reviewJson.map(option =>
                        `<option value="${option}" ${option === currentValue ? 'selected' : ''}>${option}</option>`
                    ).join('')}
                </select>`
            );
        },
        width: "150px",
        sort: false,
    },
    ...questions.map((q) => ({
        id: q.name,
        name: slugToWords(q.name),
    }))
];

// Normalize the surveyResults
surveyResults.forEach((result) => {
    questions.forEach((question) => {
        const processValue = (value) => {
            if (Array.isArray(value)) {
                if (
                    value.length > 0 &&
                    typeof value[0] === 'object' &&
                    value[0] !== null &&
                    'name' in value[0] &&
                    'type' in value[0]
                ) {
                    return `${value.length} attachment${value.length > 1 ? 's' : ''}`;
                } else {
                    return value.map(processValue).join(", ");
                }
            } else if (typeof value === 'object' && value !== null) {
                // Flatten object values only, no keys
                return Object.values(value)
                    .map(processValue)
                    .join(", ");
            } else {
                return value != null ? String(value) : '';
            }
        };

        const value = result[question.name];
        result[question.name] = processValue(value);
    });
});

document.addEventListener("DOMContentLoaded", function() {
    if (surveyResults.length > 0) {
        let grid = new gridjs.Grid({
            columns: columns,
            data: surveyResults.map((result) => {
                return Object.fromEntries(
                    Object.entries(result).map(([key, value]) => [key, value ?? ""])
                );
            }),
            autoWidth: true, // optional
            search: true,
            sort: true,
            resizable: true,
            pagination: {
                limit: 25,
                summary: true,
            }
        });

        grid.render(document.getElementById("surveyDataTable"));
        setTimeout(() => {
            document.querySelectorAll('.gridjs-tr').forEach(row => {
                const reviewTd = row.querySelector('td[data-column-id="review"]');
                const action = row.querySelector('.submission-action');

                // Hover on entire row
                row.addEventListener('mouseenter', () => {
                    // Only show if mouse is NOT over the review cell
                    row.addEventListener('mousemove', function check(e) {
                        try{
                            if (!reviewTd.contains(e.target)) {
                                action.style.left = '-10px';
                            } else {
                                action.style.left = '-32px';
                            }
                        } catch (error) {
                            // ignore
                        }
                    });
                });

                row.addEventListener('mouseleave', () => {
                    try { action.style.left = '-32px'; } catch (error) {
                        // ignore
                    }
                });
            });
        }, 1000);
    }
});

if (surveyResults.length > 0) {
    document.getElementById("exportExcel").addEventListener("click", function () {
        const headerRow = columns.map(col => col.name);
        const dataRows = surveyResults.map(row =>
            columns.map(col => row[col.id] ?? "")
        );

        const worksheet = XLSX.utils.aoa_to_sheet([headerRow, ...dataRows]);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, "Survey Results");

        var surveyTitle = ("{{ $form->title }}").toLowerCase() + "_" + new Date().toLocaleDateString();
        XLSX.writeFile(workbook, surveyTitle + ".xlsx");
    });
}

async function viewSubmissionCanvas(submissionId, element) {

    toast.info({message: "Loading submission data..."});

    const submissionRoute = "@route('collections.show', ':id')";
    const response = await fetch(submissionRoute.replace(':id', submissionId));

    if (!response.ok){
        return toast.error({message: "Failed to fetch submission data."});
    }

    const data = await response.json();
    if (!data.status) {
        return toast.error({message: data.message || "An error occurred while fetching submission data."});
    }

    showSingleCollection(data.submission);

    // show the canvas #surveySubmissionOffcanvas
    const surveySubmissionOffcanvas = new bootstrap.Offcanvas(document.getElementById("surveySubmissionOffcanvas"));
    surveySubmissionOffcanvas.show();
}

function showSingleCollection(surveyResponse) {
    var currentTheme = localStorage.getItem('phoenixTheme') ?? 'light';

    //let submission = new Survey.Model(surveyJson);
    survey.data = surveyResponse || {};
    
    // Ensure survey is in read-only mode for single collection display
    survey.mode = "display";
    
    // Reset survey to start from the first page/question
    survey.currentPageNo = 0;

    survey.render(document.getElementById("surveySubmissionBlock"));
    survey.applyTheme(currentTheme === 'light' 
        ? SurveyTheme.SolidLightPanelless 
        : SurveyTheme.SolidDarkPanelless
    );
}

// Patch localStorage.setItem to dispatch a custom event
(function() {
    const originalSetItem = localStorage.setItem;
    localStorage.setItem = function (key, value) {
        const event = new Event('phoenixThemeChanged');
        document.dispatchEvent(event);
        originalSetItem.apply(this, arguments);
    };
})();

// Listen for the custom event
document.addEventListener('phoenixThemeChanged', () => {
    const newTheme = localStorage.getItem('phoenixTheme');
    currentTheme = newTheme;
    if(currentTheme === 'dark'){
        survey.applyTheme(SurveyTheme.SolidLightPanelless);
    }
    else{
        survey.applyTheme(SurveyTheme.SolidDarkPanelless);
    }
});


function changeSubmissionReviewStatus(element) {
    const review = element.value;
    const submissionId = element.getAttribute('data-id');

    $.ajax({
        url: `@route('collections.review', $form->id)`,
        method: 'POST',
        data: {
            _token: `{{ csrf_token() }}`,
            review: review,
            submission_id: submissionId
        },
        success: function(response) {
            if(response.status){
                return toast.success({message: response.message});
            }

            return toast.error({message: response.message ?? 'An error occurred'});
        },
        error: function(error) {
            return toast.error({message: error.responseJSON.message ?? 'An error occurred'});
        }
    });
}