{{-- New zone: opened from the topbar quick actions on every page and from the zones list's empty state. --}}
<div class="modal fade" id="createZoneModal" tabindex="-1" aria-labelledby="createZoneModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="@route('zones.store')" method="POST" onsubmit="submitForm(event)">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="createZoneModalLabel">New zone</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-body-secondary fs-9 mb-3">A zone holds reusable data — countries, products, schools — that forms can load from.</p>
                    <div class="mb-3">
                        <label for="zoneName" class="form-label">Name</label>
                        <input type="text" class="form-control" id="zoneName" name="name" maxlength="50" required placeholder="e.g. Countries">
                    </div>
                    <div class="mb-0">
                        <label for="zoneDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="zoneDescription" name="description" rows="3" required placeholder="What this data is and where it is used"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-phoenix-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Create zone</button>
                </div>
            </form>
        </div>
    </div>
</div>
