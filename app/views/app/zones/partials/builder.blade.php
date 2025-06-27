<div class="offcanvas offcanvas-end" tabindex="-1" id="datazoneBuilderOffCanvas"
    aria-labelledby="datazoneBuilderOffCanvasLabel">
    <div class="offcanvas-header pb-0">
        <div>
            <h6 class="text-muted mb-3">Create Data Access URL</h6>
            <p class="small text-body-tertiary">Configure a zone and filters to generate a shareable data URL.</p>
        </div>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body pt-0">
        <div id="zoneBuilderBlock"></div>
    </div>
    <div class="offcanvas-footer p-3">
        <div class="row g-2">
            <div class="col-6">
                <button type="button" class="btn btn-outline-secondary w-100" onclick="zoneBuilder.previewData()"
                        id="previewDataBtn" disabled>
                    <i class="fa-solid fa-eye me-2"></i>Preview Data
                </button>
            </div>
            <div class="col-6">
                <button type="button" class="btn btn-success w-100" onclick="zoneBuilder.generateUrl()" 
                        id="generateUrlBtn" disabled>
                    <i class="fa-solid fa-link me-2"></i>Generate Data URL
                </button>
            </div>
        </div>
    </div>
</div>