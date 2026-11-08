{{-- Create / edit flag group modal. Filled by FlagGroupEditor (app.flags.scripts.editor). --}}
<div class="modal fade" id="flagGroupModal" tabindex="-1" aria-labelledby="flagGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-8" id="flagGroupModalLabel">New flag group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="@route('flags.store')" data-store="@route('flags.store')" data-update="@route('flags.update', ':id')"
                    method="post" onsubmit="submitForm(event)" id="flagGroupForm">
                    @csrf

                    <div class="mb-3">
                        <label for="flagGroupName" class="form-label">Group name</label>
                        <input type="text" class="form-control" id="flagGroupName" name="name" placeholder="e.g. Lead triage" maxlength="50" required>
                    </div>

                    <div class="mb-3">
                        <label for="flagGroupDescription" class="form-label">Description <span class="text-body-tertiary fw-normal">(optional)</span></label>
                        <textarea class="form-control" id="flagGroupDescription" name="description" rows="2" placeholder="What these flags are for"></textarea>
                    </div>

                    <div class="mb-2 d-flex align-items-center justify-content-between">
                        <label class="form-label mb-0">Flags</label>
                        <button type="button" class="btn btn-link btn-sm p-0" onclick="FlagGroupEditor.addRow()">
                            <i class="fa-solid fa-plus me-1"></i> Add flag
                        </button>
                    </div>
                    <div id="flagRows" class="d-flex flex-column gap-2 mb-4"></div>

                    <button type="submit" class="btn btn-primary w-100" id="flagGroupSubmit">Create flag group</button>
                </form>
            </div>
        </div>
    </div>
</div>
