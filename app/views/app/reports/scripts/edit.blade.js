const surveyJson = @json($form->content);
const survey = new Survey.Model(surveyJson);

// Utility function to validate reportDataRaw
function isValidReportDataRaw(data) {
    return data && 
           typeof data === 'object' && 
           !Array.isArray(data) && 
           data.sections && 
           Array.isArray(data.sections) && 
           data.sections.length > 0 &&
           data.sections.every(section => 
               section && 
               section.id && 
               typeof section.title === 'string'
           );
}

const questions = []
survey.getAllQuestions().forEach(q => {
    if (q.name) {
        questions.push(q.name);
    }
});

const reportData = {};

// Check if reportDataRaw is a valid object and has data
if (isValidReportDataRaw(reportDataRaw)) {
    reportDataRaw.sections.forEach(section => {
        reportData[section.id] = {
            title: section.title,
            description: section.description || '',
            attachments: Array.isArray(section.attachments) ? section.attachments : []
        };
    });
} else {
    // Log validation result for debugging
    console.log('reportDataRaw validation failed:', {
        exists: !!reportDataRaw,
        isObject: typeof reportDataRaw === 'object',
        isNotArray: !Array.isArray(reportDataRaw),
        hasSections: reportDataRaw && reportDataRaw.sections,
        sectionsIsArray: reportDataRaw && Array.isArray(reportDataRaw.sections),
        sectionsHasData: reportDataRaw && reportDataRaw.sections && reportDataRaw.sections.length > 0,
        actualValue: reportDataRaw
    });
}

let sectionOrder = Object.keys(reportData);
let sectionCount = sectionOrder.length;

function renderFromJson(store) {
    const container = document.getElementById("editor-container");
    container.innerHTML = "";
    
    // Check if reportData is empty
    if (sectionOrder.length === 0) {
        const emptyMessage = document.createElement("div");
        emptyMessage.className = "text-center py-5 text-muted";
        emptyMessage.innerHTML = `
            <div class="mb-3" style="background: url(/assets/images/vector/empty-report.png) no-repeat center center; background-size: contain; height: 460px; margin: auto; opacity: 0.3;"></div>
        `;
        container.appendChild(emptyMessage);
        return;
    }
    
    sectionOrder.forEach((id) => {
        const section = store[id];
        const row = document.createElement("div");
        row.className = "report-row";
        row.id = id;
        row.draggable = true;

        const dragHandle = document.createElement("div");
        dragHandle.className = "drag-handle";
        dragHandle.textContent = "⋮⋮";
        row.appendChild(dragHandle);

        const deleteBtn = document.createElement("button");
        deleteBtn.className = "btn position-absolute bottom-0 start-0 px-2";
        deleteBtn.innerHTML = '<i class="fas fa-trash-alt text-danger"></i>';
        deleteBtn.title = "Delete section";
        deleteBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            deleteSection(id);
        });
        row.appendChild(deleteBtn);

        const desc = document.createElement("div");
        desc.className = "report-description";
        desc.innerHTML = `
            <h2 class="editable" data-key="${id}.title">${section.title}</h2>
            <p class="editable" data-key="${id}.description">${section.description}</p>
        `;

        const attsWrapper = document.createElement("div");
        attsWrapper.className = "report-attachements";
        const select = document.createElement("select");
        select.multiple = true;
        select.setAttribute("data-custom-select", "true");

        select.dataset.sectionId = id;
        questions.forEach(q => {
            const option = document.createElement("option");
            option.value = q;
            option.textContent = q;
            if (section.attachments && Array.isArray(section.attachments) && section.attachments.includes(q)) option.selected = true;
            select.appendChild(option);
        });
        select.addEventListener("change", (e) => {
            const selected = Array.from(e.target.selectedOptions).map(o => o.value);
            reportData[e.target.dataset.sectionId].attachments = selected;
        });

        attsWrapper.appendChild(select);
        row.appendChild(desc);
        row.appendChild(attsWrapper);
        container.appendChild(row);
    });

    makeEditable();
    enableDragAndDrop();
    
    // Initialize CustomSelect for all select elements
    setTimeout(() => {
        if (window.CustomSelect) {
            new CustomSelect();
        }
    }, 100);
}

function makeEditable() {
    document.querySelectorAll(".editable").forEach(el => {
        el.addEventListener("click", () => {
            BalloonEditor
                .create(el, {
                    toolbar: ['bold', 'italic', '|', 'undo', 'redo']
                })
                .then(editor => {
                    editor.model.document.on('change:data', () => {
                        const key = el.dataset.key;
                        const value = editor.getData().replace(/<\/?[^>]+(>|$)/g, "").trim();
                        const [sectionId, field] = key.split(".");
                        reportData[sectionId][field] = value;
                    });
                })
                .catch(console.error);
        }, { once: true });
    });
}

function enableDragAndDrop() {
    const container = document.getElementById("editor-container");
    let dragged;

    container.addEventListener("dragstart", (e) => {
        dragged = e.target;
        e.target.style.opacity = 0.5;
    });

    container.addEventListener("dragend", (e) => {
        e.target.style.opacity = "";
    });

    container.addEventListener("dragover", (e) => {
        e.preventDefault();
    });

    container.addEventListener("drop", (e) => {
        e.preventDefault();
        if (e.target.closest(".report-row") && dragged !== e.target.closest(".report-row")) {
            const dropTarget = e.target.closest(".report-row");
            container.insertBefore(dragged, dropTarget.nextSibling);
            updateSectionOrder();
        }
    });
}

function updateSectionOrder() {
    const newOrder = [];
    document.querySelectorAll(".report-row").forEach(row => {
        newOrder.push(row.id);
    });
    sectionOrder = newOrder;
}

function addNewSection() {
    const newId = `section${sectionCount + 1}`;
    reportData[newId] = {
        title: `Here is Title ${sectionCount + 1}`,
        description: `Here is Description sample`,
        attachments: []
    };
    sectionOrder.push(newId);
    sectionCount++;
    renderFromJson(reportData);
    
    // Initialize CustomSelect after adding new section
    setTimeout(() => {
        if (window.CustomSelect) {
            new CustomSelect();
        }
    }, 100);
}

function deleteSection(sectionId) {
    // Prevent deletion if it's the last section
    if (sectionOrder.length <= 1) {
        Swal.fire({
            title: 'Cannot Delete',
            text: 'Cannot delete the last section. At least one section is required.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    // Confirm deletion with SweetAlert
    Swal.fire({
        title: 'Delete Section?',
        text: 'Are you sure you want to delete this section? This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Remove from reportData
            delete reportData[sectionId];
            
            // Remove from sectionOrder
            sectionOrder = sectionOrder.filter(id => id !== sectionId);
            
            // Re-render the sections
            renderFromJson(reportData);
            
            // Initialize CustomSelect after deletion
            setTimeout(() => {
                if (window.CustomSelect) {
                    new CustomSelect();
                }
            }, 100);
            
            // Show success message
            Swal.fire({
                title: 'Deleted!',
                text: 'The section has been deleted.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        }
    });
}

function exportReport() {
    const menu = [];
    const sections = [];

    sectionOrder.forEach((id, index) => {
        const content = reportData[id];
        menu.push({
            label: `${index + 1}. ${content.title}`,
            target: id
        });
        sections.push({
            id,
            title: content.title,
            description: content.description,
            attachments: content.attachments
        });
    });

    const reportJSON = { menu, sections };
    return reportJSON;
}

renderFromJson(reportData);

let saveState = false;
function handleAutoSave() {
    if (saveState) return;
    saveState = true;
    setTimeout(() => {
        saveReportContent().finally(() => {
            saveState = false;
        });
    }, 3000);
}

document.addEventListener("keydown", function(e) {
    handleAutoSave()
});

document.addEventListener("click", function() {
    handleAutoSave();
});

async function saveReportContent(publish = false) {
    const reportContent = exportReport();
    const reportFilters = filterBuilder.compileFilters();
    buttonState('#saveReportBtn', 'loading');
    
    $.ajax({
        url: "{{ route('reports.build', $report->id) }}",
        type: 'POST',
        data: {
            _token: csrf_token,
            content: JSON.stringify(reportContent),
            filters: JSON.stringify(reportFilters),
            publish: publish ? 1 : 0
        },
        success: function(response) {
            if (!response.status) {
                toast.error({
                    message: response.message || 'Failed to save report content.'
                });
                return;
            }
        },
        error: function() {
            toast.error({
                message: 'An error occurred while saving the report content.'
            });
        },
        complete: function() {
            buttonState('#saveReportBtn', 'reset', '<i class="fas fa-save"></i>');
        }
    });
}