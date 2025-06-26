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
								<div class="py-2" id="formSearchResults">
									<div class="text-center p-3">Start your search</div>
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
						let formSearchResults = document.getElementById('formSearchResults');

						formSearchResults.innerHTML = '';
						if(formResults.length === 0){
							formSearchResults.innerHTML = '<div class="text-center p-3">No results found</div>';
							return;
						}

						formResults.forEach(form => {
							let formItem = document.createElement('a');
							formItem.classList.add('p-2', 'mx-3', 'd-flex', 'search-item', 'rounded');
							formItem.href = ('@route('forms.setup', ':id', ':slug')').replace(':id', form.id).replace(':slug', form.slug);

							let formItemContent = `
								<div class="flex-">
									<h6 class="mb-0 fw-medium text-uppercase">${form.title}</h6>
									<small class="fs-9 text-muted">
										${form.description 
											? (form.description.charAt(0).toUpperCase() + form.description.slice(1).toLowerCase()).substring(0, 50) + (form.description.length > 50 ? '...' : '')
											: ''}
									</small>
								</div>
							`;

							formItem.innerHTML = formItemContent;
							formSearchResults.appendChild(formItem);
						});
					}
				}
			});
		}
	}
</script>