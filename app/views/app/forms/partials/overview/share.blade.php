{{-- Share modal: public link + QR code. --}}
<div class="modal fade" id="shareFormModal" tabindex="-1" aria-labelledby="shareFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-8" id="shareFormModalLabel">Share form</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label for="publicUrl" class="form-label">Public link</label>
                <div class="input-group mb-4">
                    <input type="text" class="form-control fo-mono" id="publicUrl" readonly value="{{ $form->publicUrl() }}">
                    <button type="button" class="btn btn-phoenix-secondary" onclick="copyToClipboard(document.getElementById('publicUrl').value)" title="Copy link">
                        <i class="fa-regular fa-clipboard"></i>
                    </button>
                    <a class="btn btn-phoenix-secondary" href="{{ $form->publicUrl() }}" target="_blank" title="Open">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                </div>

                <div class="d-flex align-items-center gap-4">
                    <div id="qrcode" class="fo-qr"></div>
                    <div>
                        <p class="fs-9 text-body-tertiary mb-2">Print or post the QR code; it opens the same link.</p>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="downloadQRCode(`{{ $form->slug }}`)">
                            <i class="fa fa-download me-1"></i> Download QR code
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
