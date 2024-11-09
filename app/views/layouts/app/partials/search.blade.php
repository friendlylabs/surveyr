<div class="modal fade" id="searchBoxModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="true" data-phoenix-modal="data-phoenix-modal" style="--phoenix-backdrop-opacity: 1;">
	<div class="modal-dialog">
		<div class="modal-content mt-15 rounded-pill">
			<div class="modal-body p-0">
				<div class="search-box navbar-top-search-box" data-list='{"valueNames":["title"]}' style="width: auto;">
					<form class="position-relative" data-bs-toggle="search" data-bs-display="static">
						<input class="form-control search-input rounded-pill form-control-lg" id="globalSearch" type="search" placeholder="Search..." aria-label="Search" oninput="initSearchBox()">
						<span class="fas fa-search search-box-icon"></span>
					</form>
					<div class="btn-close position-absolute end-0 top-50 translate-middle cursor-pointer shadow-none" data-bs-dismiss="search">
						<button class="btn btn-link p-0" aria-label="Close"></button>
					</div>
					<div class="dropdown-menu border start-0 py-0 overflow-hidden w-100">
						<div class="scrollbar-overlay" style="max-height: 30rem;">
							<div class="list pb-3">

								<h6 class="dropdown-header text-body-highlight fs-9 border-bottom border-translucent py-2 lh-sm">Forms</h6>
								<div class="py-2" id="formSearchResults">
									<!--a class="dropdown-item py-2 d-flex align-items-center" href="/form/setupId">
										<div class="flex-1">
											<h6 class="mb-0 text-body-highlight title">Form Name</h6>
											<p class="fs-10 mb-0 d-flex text-body-tertiary">
												<span class="fw-medium text-body-tertiary text-opactity-85">Form Description</span>
											</p>
										</div>
									</a-->
								</div>

								<hr class="my-0" />
								<h6 class="dropdown-header text-body-highlight fs-9 border-bottom border-translucent py-2 lh-sm">Spaces</h6>
								<div class="py-2" id="spaceSearchResults">
									<!--a class="dropdown-item" href="/space/setup/id">
										<div class="d-flex align-items-center">
											<div class="fw-normal text-body-highlight title">
												<span class="fa-solid fa-cycle text-body" data-fa-transform="shrink-2"></span>
												Space Name
											</div>
										</div>
									</a-->
								</div>

							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
	function initSearchBox(){
		let searchText = document.getElementById('globalSearch').value;
		if(searchText.length > 0){

			$.ajax({
				url: '@route('forms.search')',
				type: 'GET',
				data: {
					q: searchText
				},
				success: function(response){
					if(response.status){
						let formResults = response.forms;
						let spaceResults = response.spaces;

						let formSearchResults = document.getElementById('formSearchResults');
						let spaceSearchResults = document.getElementById('spaceSearchResults');

						formSearchResults.innerHTML = '';
						spaceSearchResults.innerHTML = '';

						formResults.forEach(form => {
							let formItem = document.createElement('a');
							formItem.classList.add('dropdown-item', 'py-2', 'd-flex', 'align-items-center');
							formItem.href = ('@route('forms.setup', ':id', ':slug')').replace(':id', form.id).replace(':slug', form.slug);

							let formItemContent = `
								<div class="flex-1">
									<h6 class="mb-0 text-body-highlight title">${form.title}</h6>
									<p class="fs-10 mb-0 d-flex text-body-tertiary">
										<span class="fw-medium text-body-tertiary text-opactity-85">${form.description}</span>
									</p>
								</div>
							`;

							formItem.innerHTML = formItemContent;
							formSearchResults.appendChild(formItem);
						});

						spaceResults.forEach(space => {
							let spaceItem = document.createElement('a');
							spaceItem.classList.add('dropdown-item');
							spaceItem.href = ('@route('spaces.show', ':id')').replace(':id', space.id);

							let spaceItemContent = `
								<div class="d-flex align-items-center">
									<div class="fw-normal text-body-highlight title">
										<span class="fa-solid fa-cycle text-body" data-fa-transform="shrink-2"></span>
										${space.name}
									</div>
								</div>
							`;

							spaceItem.innerHTML = spaceItemContent;
							spaceSearchResults.appendChild(spaceItem);
						});
					}
				}
			});
		}
	}
</script>