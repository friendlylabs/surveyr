<!-- Create Report Modal -->
<div class="modal fade" id="createReportModal" tabindex="-1" aria-labelledby="createReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fs-8" id="createReportModalLabel">Create Report</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="@route('reports.store')" method="post" onsubmit="submitForm(event)">

                    @csrf
                    <input type="hidden" name="form_id" value="{{ $form->id }}">

                    <div class="mb-3">
                        <label for="title" class="form-label required">Report Title</label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="Report Title" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label required">Description</label>
                        <textarea class="form-control" id="description" name="description" placeholder="Report description" rows="3" required></textarea>
                    </div>
                    
                    <!-- collaborators, search true -->
                    <div class="mb-3">
                        <label for="collaborators" class="form-label">Collaborators</label>
                        <select class="form-selector" id="collaborators" name="collaborators[]" multiple style="visibility: hidden;" data-search="true">
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" {{ in_array($user->id, $form->collaborators) ? 'selected' : '' }}>{{ $user->fullname }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- submit button -->
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Create Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>