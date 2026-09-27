<div class="modal fade" id="addDatazoneModal" tabindex="-1" aria-labelledby="addDatazoneModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDatazoneModalLabel">Add Datazone</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="@route('zones.store')" method="POST" onsubmit="submitForm(event)">
                    @csrf
                    <div class="mb-3">
                        <label for="zoneName" class="form-label">Name</label>
                        <input type="text" class="form-control" id="zoneName" name="name" required
                            placeholder="Enter zone name i.e countries, products etc">
                    </div>
                    <div class="mb-3">
                        <label for="zoneDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="zoneDescription" name="description" rows="3"
                            placeholder="Enter zone description" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Add Zone</button>
                </form>
            </div>
        </div>
    </div>
</div>