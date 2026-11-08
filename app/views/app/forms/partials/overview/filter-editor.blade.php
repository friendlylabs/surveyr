{{-- Saved filter editor (create / edit). The rule UI is CollectionFilterView, scoped by data-role. --}}
<div class="offcanvas offcanvas-end fo-filter-editor" tabindex="-1" id="filterEditorOffcanvas" aria-labelledby="filterEditorLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="filterEditorLabel">New filter</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <form class="offcanvas-body d-flex flex-column" id="filterEditorForm"
        data-store="@route('collections.filters.store', $form->id)" data-update="@route('collections.filters.update', ':id')">

        <div class="mb-3">
            <label class="form-label" for="filterName">Name</label>
            <input type="text" class="form-control" id="filterName" name="name" maxlength="120" placeholder="e.g. Flagged for follow-up" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="filterDescription">Description</label>
            <input type="text" class="form-control" id="filterDescription" name="description" maxlength="300" placeholder="What this filter shows">
        </div>

        <div class="fo-filter-rules" id="filterEditorRules">
            <div data-role="fixed-filters"></div>
            <div data-role="rules"></div>
            <button type="button" class="btn btn-outline-primary btn-sm" data-role="add-rule">
                <i class="fa-solid fa-plus me-1"></i> Add rule
            </button>
        </div>

        <div class="mt-auto pt-4">
            <button type="submit" class="btn btn-primary w-100" id="filterEditorSubmit">Save filter</button>
        </div>
    </form>
</div>
