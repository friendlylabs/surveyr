<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasFormRules" aria-labelledby="offcanvasFormRulesLabel" style="width: 100%; max-width: 680px;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="offcanvasFormRulesLabel">
            <i class="fa-solid fa-sliders me-2"></i>Visualization Rules
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body p-0">

        {{-- Loading state --}}
        <div id="rulesLoading" class="d-flex align-items-center justify-content-center py-5">
            <div class="spinner-border spinner-border-sm text-secondary me-2"></div>
            <span class="text-body-tertiary small">Loading questions...</span>
        </div>

        {{-- Rules editor --}}
        <div id="rulesEditor" class="d-none">
            <div class="px-3 py-3 border-bottom bg-body-tertiary">
                <p class="small text-body-secondary mb-2">
                    Pick what gets charted. Numeric questions can be bound to a label field, so
                    "Number of Computers" is charted per school instead of as one meaningless average.
                </p>
                <div class="d-flex align-items-center gap-2">
                    <span id="rulesSummary" class="small text-body-tertiary flex-grow-1"></span>
                    <button type="button" id="rulesEnableAll" class="btn btn-link btn-sm p-0">Enable all</button>
                    <span class="text-body-tertiary">&middot;</span>
                    <button type="button" id="rulesDisableAll" class="btn btn-link btn-sm p-0">Disable all</button>
                </div>
            </div>

            <div id="rulesList" class="px-3 py-3"></div>
        </div>

    </div>

    <div class="offcanvas-footer border-top px-3 py-3 d-flex gap-2">
        <button id="saveRulesBtn" class="btn btn-primary btn-sm flex-grow-1">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save Rules
        </button>
        <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="offcanvas">Cancel</button>
    </div>
</div>

<style>
    #offcanvasFormRules .rule-card { transition: opacity .2s; }
    #offcanvasFormRules .rule-card.is-hidden { opacity: .5; }
    #offcanvasFormRules .rule-card-body { background: var(--bs-body-bg); }
    #offcanvasFormRules .offcanvas-footer { background: var(--bs-body-bg); }

    /* grouped visualizer, rendered by viz-engine */
    .sa-bound__empty {
        padding: 2rem 1rem;
        text-align: center;
        color: #909090;
        font-size: .875rem;
    }
</style>
