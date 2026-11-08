{{-- Share modal: generate / copy / revoke the public link. Expects $report. --}}
<div class="modal fade" id="shareReportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Share report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="shareLinkContainer" class="d-none">
                    <label class="form-label">Public link</label>
                    <div class="input-group mb-2">
                        <input type="text" class="form-control" id="shareLink" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyShareLink(this)" title="Copy link">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                    </div>
                    <p class="text-body-tertiary small mb-3">Anyone with this link can view the published report.</p>
                    <button class="btn btn-outline-danger btn-sm" onclick="deleteShareLink()">
                        <i class="fa-solid fa-trash me-2"></i>Revoke link
                    </button>
                </div>
                <div id="noShareLinkContainer">
                    <p class="text-body-tertiary">This report is private. Generate a public link to share the published version.</p>
                    <button class="btn btn-primary btn-sm" onclick="generateShareLink()">
                        <i class="fa-solid fa-link me-2"></i>Generate public link
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const headers = {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf_token,
        };

        let hasLink = @json((bool) $report->link);

        function showLinkUI(url) {
            document.getElementById('shareLink').value = url;
            document.getElementById('shareLinkContainer').classList.remove('d-none');
            document.getElementById('noShareLinkContainer').classList.add('d-none');
        }

        function showNoLinkUI() {
            document.getElementById('shareLinkContainer').classList.add('d-none');
            document.getElementById('noShareLinkContainer').classList.remove('d-none');
        }

        window.shareReport = function () {
            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('shareReportModal'));
            // an existing link is returned by the same endpoint without creating a new one
            if (hasLink) return window.generateShareLink(true).then(() => modal.show());
            showNoLinkUI();
            modal.show();
        };

        window.generateShareLink = function (quiet) {
            return fetch('@route("reports.generateLink", $report->id)', { method: 'POST', headers })
                .then(r => r.json())
                .then(data => {
                    if (!data.status || !data.url) throw new Error(data.message || 'Failed to generate link');
                    hasLink = true;
                    showLinkUI(data.url);
                    if (!quiet) toast.success({ message: data.message });
                })
                .catch(error => toast.error({ message: error.message || 'Failed to generate share link' }));
        };

        window.deleteShareLink = function () {
            if (!confirm('Revoke this public link? Anyone using it will lose access.')) return;

            fetch('@route("reports.deleteLink", $report->id)', { method: 'POST', headers })
                .then(r => r.json())
                .then(data => {
                    if (!data.status) throw new Error(data.message || 'Failed to revoke link');
                    hasLink = false;
                    showNoLinkUI();
                    toast.success({ message: data.message });
                })
                .catch(error => toast.error({ message: error.message || 'Failed to revoke share link' }));
        };

        window.copyShareLink = function (button) {
            const input = document.getElementById('shareLink');
            const done = () => {
                const original = button.innerHTML;
                button.innerHTML = '<i class="fa-solid fa-check"></i>';
                setTimeout(() => { button.innerHTML = original; }, 1500);
            };

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(input.value).then(done);
            } else {
                input.select();
                document.execCommand('copy');
                done();
            }
        };
    })();
</script>
