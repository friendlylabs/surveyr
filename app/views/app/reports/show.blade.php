@extends('layouts.app.main')

@style('/vendor/surveyjs/survey-core.min.css','src')
@style('/vendor/surveyjs/survey.analytics.min.css','src')
@style('app.reports.styles.report')

@section('content')
    <div class="content">
        <div class="report-detail d-none text-center">
            <h3 class="fs-7">{{ $report->title }}</h3>
            <p class="text-muted">{{ $report->description }}</p>
        </div>
        <div id="reportContainer" class="doc-layout"></div>
    </div>

    <!-- edit button -->
    @if($accessRole !== 'viewer')
        <div class="position-fixed end-0 bottom-0 p-3 edit-report-btn">
            <div class="d-flex flex-column gap-2">
                <button class="btn btn-info btn-sm" onclick="shareReport()">
                    <i class="fa-solid fa-share-nodes me-2"></i>
                    Share Report
                </button>
                <a href="@route('reports.edit', $report->id)" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-pencil me-2"></i>
                    Edit Report
                </a>
            </div>
        </div>
    @endif
    
    <div id="surveyVizPanel" class="py-0 d-none"></div>

    <!-- Share Modal -->
    <div class="modal fade" id="shareReportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Share Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="shareLinkContainer" class="d-none">
                        <div class="mb-3">
                            <label class="form-label">Public Link</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="shareLink" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="copyShareLink()">
                                    <i class="fa-solid fa-copy"></i>
                                </button>
                            </div>
                            <small class="text-muted">Anyone with this link can view the report</small>
                        </div>
                        <button class="btn btn-danger btn-sm" onclick="deleteShareLink()">
                            <i class="fa-solid fa-trash me-2"></i>
                            Delete Link
                        </button>
                    </div>
                    <div id="noShareLinkContainer">
                        <p class="text-muted">This report is not currently shared. Generate a public link to share it.</p>
                        <button class="btn btn-primary" onclick="generateShareLink()">
                            <i class="fa-solid fa-link me-2"></i>
                            Generate Public Link
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentShareLink = null;

        function shareReport() {
            // Check if link already exists
            fetch('@route("reports.generateLink", $report->id)', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status && data.url) {
                    currentShareLink = data.url;
                    showShareLinkUI(data.url);
                } else {
                    showNoLinkUI();
                }
                const modal = new bootstrap.Modal(document.getElementById('shareReportModal'));
                modal.show();
            })
            .catch(error => {
                console.error('Error:', error);
                toast.error({message: 'Failed to check share status'});
            });
        }

        function generateShareLink() {
            fetch('@route("reports.generateLink", $report->id)', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf_token
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    currentShareLink = data.url;
                    showShareLinkUI(data.url);
                    toast.success({message: data.message});
                } else {
                    toast.error({message: data.message || 'Failed to generate link'});
                }
            })
            .catch(error => {
                console.error('Error:', error);
                toast.error({message: 'Failed to generate share link'});
            });
        }

        function deleteShareLink() {
            if (!confirm('Are you sure you want to delete this public link? It will no longer be accessible.')) {
                return;
            }

            fetch('@route("reports.deleteLink", $report->id)', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf_token
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    currentShareLink = null;
                    showNoLinkUI();
                    toast.success({message: data.message});
                } else {
                    toast.error({message: data.message || 'Failed to delete link'});
                }
            })
            .catch(error => {
                console.error('Error:', error);
                toast.error({message: 'Failed to delete share link'});
            });
        }

        function copyShareLink() {
            const linkInput = document.getElementById('shareLink');
            linkInput.select();
            document.execCommand('copy');
            
            // Show feedback
            const copyBtn = event.target.closest('button');
            const originalHTML = copyBtn.innerHTML;
            copyBtn.innerHTML = '<i class="fa-solid fa-check"></i>';
            setTimeout(() => {
                copyBtn.innerHTML = originalHTML;
            }, 2000);
        }

        function showShareLinkUI(url) {
            document.getElementById('shareLink').value = url;
            document.getElementById('shareLinkContainer').classList.remove('d-none');
            document.getElementById('noShareLinkContainer').classList.add('d-none');
        }

        function showNoLinkUI() {
            document.getElementById('shareLinkContainer').classList.add('d-none');
            document.getElementById('noShareLinkContainer').classList.remove('d-none');
        }

        document.addEventListener('DOMContentLoaded', function () {
            // Initialize the report attachments
            
        });

    
    </script>
        
@endsection

@script('/vendor/surveyjs/survey.core.min.js','src')
@script('/vendor/surveyjs/survey-js-ui.min.js','src')
@script('/vendor/surveyjs/plotly.min.js','src')
@script('/vendor/surveyjs/survey.analytics.min.js','src')
@script('app.reports.scripts.report')