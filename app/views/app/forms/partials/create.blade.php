<div class="modal fade" id="createFormModal" tabindex="-1" aria-labelledby="createFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createFormModalLabel">Create Form</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="@route('forms.build')" method="POST" onsubmit="submitForm(event)">
                    @csrf
                    <div class="mb-3">
                        <label for="title" class="form-label">Form Title</label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="Customer Feedback Form" required>
                    </div>
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control date-selector" id="start_date" name="start_date" 
                            value="{{ substring(now(), 10, null) }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control date-selector" id="end_date" name="end_date" 
                            value="{{ substring(now()->addDays(7), 10, null) }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="form to collect feedback from your customers ..."></textarea>
                    </div>

                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary w-100">Create Form</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>