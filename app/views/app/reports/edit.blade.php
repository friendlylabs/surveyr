@extends('layouts.app.main')
@section('content')
	<style>
		.doc-content { margin: auto; }
		.report-row { border: 1px solid #ddd; padding: 16px; margin-bottom: 16px; border-radius: 6px; background: #fff; }
		.report-description h2, .report-description p { margin: 0.5rem 0; }
		.editable { cursor: pointer; background: #fdfdfd; }
		.report-attachements { font-size: 0.9em; color: #555; margin-top: 10px; }
		.drag-handle { cursor: move; margin-bottom: 8px; font-weight: bold; }

		.report-row {
				position: relative;
				display: flex;
				gap: 2rem;
				align-items: flex-start;
				margin-bottom: 1rem;
		}

		/* Sticky .report-attachements */
		.report-attachements {
				flex: 1;
				min-width: 250px;
				border-radius: 6px;
				overflow-x: auto;

				position: sticky;
				top: 1rem;
				align-self: start;
				/* Important: don't stretch/
				max-height: fit-content; */
				overflow: hidden;
				width: 100%;
				height: 200px
		}

		.report-description {
				flex: 1.5;
				min-width: 250px;
		}

		.bootstrap-select>.dropdown-menu>.inner{
				height: 140px !important;
				overflow-y: auto !important;
		}

		/* overides */
		.cs-dropdown{
			max-height: 150px;
		}

		#applyFiltersBtn{
			display: none;
		}

		#addRuleBtn{
			width: 100%;
		}

		.filter-action-btns {
			max-width: 1024px;
		}
	</style>

	<div class="content">
		<div class="row">
			<div class="col-12 mb-4 position-relative">
				<h3 class="fs-7">{{ $report->title }}</h3>

				<div class="position-absolute end-5 top-0">

					<button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#generateReportModal" onclick="underDevelopment()">
							<i class="fa fa-robot"></i>
					</button>

					@if($report->user_id == auth()->id() || in_array(auth()->id(), $report->collaborators))
						<button class="btn btn-primary btn-sm" data-bs-toggle="offcanvas"
								data-bs-target="#reportSettingsOffcanvas" aria-controls="reportSettingsOffcanvas">
								<i class="fa-solid fa-filter me-2"></i> Filters
						</button>
					@endif
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-12">
				<div id="editor-container" class="doc-content"></div>
				
				<div class="position-fixed d-flex flex-column align-items-center" style="top: 40%; right: 32px; z-index: 1050; gap: 16px;">
					<button 
						class="btn btn-primary btn-sm rounded-circle mb-2" 
						onclick="addNewSection()" 
						data-bs-toggle="tooltip" 
						data-bs-placement="left" 
						title="Add Section"
						style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
						<i class="fa fa-plus"></i>
					</button>
					<a 
						href="@route('reports.show', $report->id)?preview=true" target="_blank"
						class="btn btn-secondary btn-sm rounded-circle mb-2" 
						data-bs-toggle="tooltip" 
						data-bs-placement="left" 
						title="Preview"
						style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
						<i class="fa fa-terminal"></i>
					</a>
					<button 
						class="btn btn-warning btn-sm rounded-circle mb-2" 
						onclick="resetDraft()" 
						data-bs-toggle="tooltip" 
						data-bs-placement="left" 
						title="Reset Draft"
						style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
						<i class="fa fa-undo"></i>
					</button>
					<button 
						class="btn btn-success btn-sm rounded-circle" 
						onclick="saveReportContent(true)" 
						data-bs-toggle="tooltip" 
						data-bs-placement="left" 
						title="Publish"
						id="saveReportBtn"
						style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
						<i class="fa fa-save"></i>
					</button>
				</div>
			</div>
		</div>
	</div>

	<!-- report settings offcanvas -->
	<div class="offcanvas offcanvas-bottom w-100" tabindex="-1" id="reportSettingsOffcanvas" aria-labelledby="reportSettingsOffcanvasLabel"
		style="height: 100vh; overflow-y: auto;">
		<div class="offcanvas-header">
				<h5 class="offcanvas-title" id="reportSettingsOffcanvasLabel">Report Settings</h5>
				<button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
		</div>
		<div class="offcanvas-body">
			<div id="fixedFilterContainer"></div>
			<div id="filterRuleContainer"></div>
		</div>

		<div class="offcanvas-footer p-5">
			<div class="w-100 mx-auto filter-action-btns">
				<button id="addRuleBtn" class="btn btn-outline-primary mt-3">Add Filter Rule</button>
				<button id="applyFiltersBtn" class="btn btn-success mt-3 ms-2">Apply Filters</button>
			</div>
		</div>
	</div>

	<script>
		const reportDataRaw = @json($report->draft);
	</script>

@endsection
@script('/vendor/surveyjs/survey.core.min.js','src')

@style('/vendor/flatpickr/flatpickr.min.css','src')
@script('/vendor/flatpickr/flatpickr.min.js','src')
@script('/vendor/cselect/select.js', 'src')
@script('/vendor/ckeditor5/balloon.min.js', 'src')

@script('app.reports.scripts.edit')
@script('app.collections.scripts.utils')
@script('app.collections.scripts.filter')