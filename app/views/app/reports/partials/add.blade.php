{{-- Create report modal. Expects $forms (accessible forms), $users; $form preselects a source when the page is scoped to one.
     Included globally by layouts.app.partials.create. --}}
<div class="modal fade" id="createReportModal" tabindex="-1" aria-labelledby="createReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title fs-8" id="createReportModalLabel">Create report</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="@route('reports.store')" method="post" onsubmit="submitForm(event)">
                    @csrf

                    <div class="mb-3">
                        <label for="newReportTitle" class="form-label required">Report title</label>
                        <input type="text" class="form-control" id="newReportTitle" name="title" placeholder="Report title" required>
                    </div>

                    <div class="mb-3">
                        <label for="newReportDescription" class="form-label required">Description</label>
                        <textarea class="form-control" id="newReportDescription" name="description" placeholder="What this report is about" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="newReportForms" class="form-label required">Source forms</label>
                        <select class="form-selector" id="newReportForms" name="forms[]" multiple style="visibility: hidden;" data-search="true" data-placeholder="Pick one or more forms" required>
                            @foreach ($forms as $source)
                                <option value="{{ $source->id }}" {{ isset($form) && $form->id == $source->id ? 'selected' : '' }}>{{ $source->title }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Visuals in the report can come from any of these forms; more can be added later.</div>
                    </div>

                    <div class="mb-3">
                        <label for="newReportCollaborators" class="form-label">Collaborators</label>
                        <select class="form-selector" id="newReportCollaborators" name="collaborators[]" multiple style="visibility: hidden;" data-search="true">
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->fullname }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Create report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
