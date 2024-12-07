<div class="modal fade" id="aiFormModal" tabindex="-1" aria-labelledby="aiFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="aiFormModalLabel">AI Form Generator</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="@route('forms.generate')" method="POST" onsubmit="submitForm(event)">
                    @csrf
                    <div class="mb-3">
                        <label for="formTitle" class="form-label">Form Title</label>
                        <input type="text" class="form-control" id="formTitle" name="title" placeholder="i.e Student Registration Form" required>
                    </div>
                    <div class="mb-3">
                        <label for="formDescription" class="form-label">Form Description</label>
                        <textarea class="form-control" id="formDescription" name="description" rows="5" placeholder="Your Form description" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Generate</button>
                </form>
            </div>
        </div>
    </div>
</div>