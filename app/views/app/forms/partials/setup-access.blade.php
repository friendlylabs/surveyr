<div class="card fo-settings-card" id="accessSettings">
    <div class="card-header py-3">
        <h5 class="card-title mb-0">Access</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('forms.setup.update', 'access', $form->id) }}" method="POST" id="accessSetupForm" onsubmit="submitForm(event)">
            @csrf

            <!-- switch: form has no closing date -->
            <div class="mb-3">
                <div class="form-check form-switch position-relative">
                    <!-- on check, reset and disable closing date -->
                    <input class="form-check-input position-absolute end-0" type="checkbox" id="noClosingDate" value="1" name="is_indefinite" {{ $form->is_indefinite ? 'checked' : '' }} onchange="toggleClosingDate()">
                    <label class="form-check-label position-absolute start-0 fw-bold" for="noClosingDate">No Closing Date</label>
                </div>
            </div>

            <script>
                function toggleClosingDate() {
                    const closingDate = document.getElementById('endDate');
                    closingDate.disabled = !closingDate.disabled;
                    closingDate.value = closingDate.disabled ? '' : closingDate.value;
                    closingDate.readOnly = closingDate.disabled;
                }
            </script>

            <div class="mb-3">
                <label for="startDate" class="form-label">Opening Date</label>
                <input type="date" class="form-control date-selector" id="startDate" name="start_date" placeholder="opening date" value="{{ $form->start_date?->format('Y-m-d') }}">
            </div>

            <div class="mb-3">
                <label for="endDate" class="form-label">Closing Date</label>
                <input type="date" class="form-control date-selector" id="endDate" name="end_date" placeholder="closing date" value="{{ $form->end_date?->format('Y-m-d') }}">
            </div>

            <div class="mb-3">
                <label for="accessCode" class="form-label">Access Password</label>
                <small class="text-muted fs-10 ms-3 d-block">Set a password to restrict access to this form (under development)</small>
                <input type="password" placeholder="leave empty to allow public access" class="form-control" id="accessCode" name="access_code" value="{{ $form->access_code }}">
            </div>

            <div class="mt-5">
                <button type="submit" class="btn btn-primary btn-sm w-100">Update Access Details</button>
            </div>
        </form> 
    </div>
</div>