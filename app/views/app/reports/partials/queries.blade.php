{{-- SurveyQL queries offcanvas: named sources and expressions for inline query:{…} answers, driven by edit.blade.js. --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="reportQueriesOffcanvas" aria-labelledby="reportQueriesLabel" style="width: 100%; max-width: 620px;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="reportQueriesLabel">Queries</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column gap-4">

        <div class="d-flex flex-column gap-2">
            <div class="d-flex align-items-center justify-content-between">
                <span class="rp-kicker">Sources</span>
                <button type="button" class="btn btn-sm btn-link p-0" data-q-add-source><i class="fa-solid fa-plus me-1"></i>Add source</button>
            </div>
            <span class="small text-body-tertiary">A name for each form's submissions, used to start a query.</span>
            <div id="reportQuerySources" class="d-flex flex-column gap-2"></div>
        </div>

        <div class="d-flex flex-column gap-2">
            <div class="d-flex align-items-center justify-content-between">
                <span class="rp-kicker">Expressions</span>
                <button type="button" class="btn btn-sm btn-link p-0" data-q-add-expression><i class="fa-solid fa-plus me-1"></i>Add expression</button>
            </div>
            <span class="small text-body-tertiary">Named queries, run in order: a later one can build on an earlier one. Use them as <code>query:{name}</code>.</span>
            <div id="reportQueryExpressions" class="d-flex flex-column gap-2"></div>
        </div>

        <div class="d-flex flex-column gap-2">
            <span class="rp-kicker">Try a query</span>
            <input type="text" id="reportQueryTry" class="form-control form-control-sm font-monospace" placeholder="hh | avg(age) where gender is &quot;female&quot;" autocomplete="off" spellcheck="false">
            <div id="reportQueryTryResult" class="small"></div>
        </div>
    </div>
</div>
