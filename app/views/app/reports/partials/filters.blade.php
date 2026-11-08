{{-- Report filters offcanvas: one filter panel per source form, built by edit.blade.js (FilterBuilder). --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="reportFiltersOffcanvas" aria-labelledby="reportFiltersLabel" style="width: 100%; max-width: 680px;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="reportFiltersLabel">Report filters</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <p class="small text-body-secondary mb-3">
            Only submissions matching these filters feed the charts. Each source form has its own set; filters are saved with the draft.
        </p>
        <select id="reportFilterForms" class="rf-input rf-form-select mb-3" aria-label="Source form" hidden></select>
        <div id="reportFilterPanels"></div>
    </div>
    <div class="offcanvas-footer border-top px-3 py-3 d-flex gap-2">
        <button type="button" id="reportApplyFilters" class="btn btn-primary btn-sm flex-grow-1">
            <i class="fa-solid fa-check me-1"></i>Apply filters
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="offcanvas">Close</button>
    </div>
</div>
