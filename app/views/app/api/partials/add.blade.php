<div class="modal fade" id="createApiKeyModal" tabindex="-1" aria-labelledby="createApiKeyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createApiKeyModalLabel">Issue API Key</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="@route('keys.create')" method="POST" onsubmit="submitForm(event)">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="e.g. Wordpress Key" required>
                    </div>
                    <div class="mb-3">
                        <label for="passphrase" class="form-label">Passphrase</label>
                        <input type="text" class="form-control" id="passphrase" name="passphrase" placeholder="e.g. 123456" required>
                        <small>Passphrase is used to authenticate the key, make sure to keep it safe.</small>
                    </div>
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary w-100">Generate</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>