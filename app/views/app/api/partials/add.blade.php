{{-- Issue an API key. After a successful POST the same modal reveals the token once; the list only ever shows a prefix. --}}
<div class="modal fade" id="createApiKeyModal" tabindex="-1" aria-labelledby="createApiKeyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createApiKeyModalLabel">Issue API key</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            {{-- step 1: details --}}
            <form id="createApiKeyForm" action="@route('keys.create')" method="POST" onsubmit="submitForm(event, apiKeyIssued)" autocomplete="off">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="apiKeyName" class="form-label">Name</label>
                        <input type="text" class="form-control" id="apiKeyName" name="name" maxlength="50" placeholder="e.g. WordPress site" required>
                        <small class="text-body-tertiary">Where this key will be used, so you can tell keys apart.</small>
                    </div>
                    <div class="mb-3">
                        <label for="apiKeyPassphrase" class="form-label">Passphrase</label>
                        <input type="password" class="form-control" id="apiKeyPassphrase" name="passphrase" minlength="8" placeholder="At least 8 characters" required>
                        <small class="text-body-tertiary mt-3">
                            Sent as <code>passphrase</code> with every request alongside the key. It is hashed and cannot be recovered later.
                        </small>
                    </div>
                    <p class="text-body-tertiary fs-10 mb-0">
                        <i class="fa-regular fa-clock me-1"></i> Keys expire 12 months after they are issued.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-phoenix-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Issue key</button>
                </div>
            </form>

            {{-- step 2: reveal --}}
            <div id="createApiKeyResult" class="d-none">
                <div class="modal-body">
                    <p class="text-body-secondary fs-9 mb-3">
                        <strong class="text-body-emphasis" data-key-name></strong> is ready. Copy the key now and keep it with its passphrase; anyone holding both can call the API as you.
                    </p>

                    <div class="mb-3">
                        <label for="issuedApiKey" class="form-label">API key</label>
                        <div class="input-group">
                            <input type="text" class="form-control font-monospace fs-10" id="issuedApiKey" readonly>
                            <button type="button" class="btn btn-primary" id="copyIssuedApiKey" title="Copy key">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        </div>
                        <small class="text-body-tertiary" data-key-expiry></small>
                    </div>

                    <span class="text-body-tertiary text-uppercase fs-10 fw-bold d-block mb-2">How to use it</span>
                    <div class="api-key-usage">
                        <div class="d-flex gap-3">
                            <span class="text-body-tertiary">Header</span>
                            <span class="text-body-emphasis">Authorization: Bearer <em>&lt;key&gt;</em></span>
                        </div>
                        <div class="d-flex gap-3">
                            <span class="text-body-tertiary">Param | Body</span>
                            <span class="text-body-emphasis">passphrase=<em>&lt;your passphrase&gt;</em></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary btn-sm" onclick="window.location.reload()">Done</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .api-key-usage {
        display: grid;
        gap: 6px;
        padding: 12px 14px;
        border-radius: 10px;
        background: var(--phoenix-emphasis-bg);
        border: 1px dashed var(--phoenix-border-color);
        font-family: var(--phoenix-font-monospace, ui-monospace, SFMono-Regular, Menlo, Consolas, monospace);
        font-size: 12px;
    }
    .api-key-usage > div > span:first-child { min-width: 52px; font-family: var(--phoenix-body-font-family, inherit); }
    .api-key-usage em { font-style: normal; color: var(--phoenix-primary); }
</style>

<script>
    // swap the form for the one-time reveal; reloading (Done / close) refreshes the list
    function apiKeyIssued(response) {
        if (!response.status) {
            toast.error({ message: response.message ?? 'Could not issue the key' });
            return;
        }

        const modal = document.getElementById('createApiKeyModal');
        modal.querySelector('[data-key-name]').textContent = response.key.name;
        modal.querySelector('#issuedApiKey').value = response.key.token;
        modal.querySelector('[data-key-expiry]').textContent = response.key.expires_at ? 'Expires ' + response.key.expires_at + '.' : '';
        modal.querySelector('.modal-title').textContent = 'API key issued';

        document.getElementById('createApiKeyForm').classList.add('d-none');
        document.getElementById('createApiKeyResult').classList.remove('d-none');
        modal.dataset.issued = '1';
    }

    document.getElementById('copyIssuedApiKey').addEventListener('click', function () {
        const input = document.getElementById('issuedApiKey');
        input.select();
        copyToClipboard(input.value);
        this.innerHTML = '<i class="fa-solid fa-check"></i>';
        clearTimeout(this._reset);
        this._reset = setTimeout(() => { this.innerHTML = '<i class="fa-regular fa-copy"></i>'; }, 2000);
    });

    document.getElementById('createApiKeyModal').addEventListener('hidden.bs.modal', function () {
        if (this.dataset.issued) window.location.reload();
    });
</script>
