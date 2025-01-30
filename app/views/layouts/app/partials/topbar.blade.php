<nav class="navbar navbar-top fixed-top navbar-expand-lg" id="navbarTop">
	<div class="navbar-logo">

        <button class="btn navbar-toggler navbar-toggler-humburger-icon hover-bg-transparent" onclick="menuToOffcanvas()">
            <span class="navbar-toggle-icon">
                <span class="toggle-line"></span>
            </span>
        </button>

		<a class="navbar-brand me-1 me-sm-3" href="javascript:void(0)">
			<div class="d-flex align-items-center">
				<div class="d-flex align-items-center">
                    <a href="@route('app.home')"><img src="/assets/images/brand/logo.png" alt="phoenix" width="100" /></a>
				</div>
			</div>
		</a>
	</div>

	@include('layouts.app.partials.sidebar')

	<ul class="navbar-nav navbar-nav-icons flex-row">
		<li class="nav-item">
            <div class="theme-control-toggle px-2">
                <input class="form-check-input ms-0 theme-control-toggle-input" type="checkbox" data-theme-control="phoenixTheme" value="dark" id="themeControlToggle">
                <label class="mb-0 theme-control-toggle-label theme-control-toggle-light" for="themeControlToggle" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Switch theme" style="height:32px;width:32px;">
                    <span class="fa-solid fa-sun fs-8"></span>
                </label>
                <label class="mb-0 theme-control-toggle-label theme-control-toggle-dark" for="themeControlToggle" data-bs-toggle="tooltip" data-bs-placement="left" data-bs-title="Switch theme" style="height:32px;width:32px;">
                    <span class="fa-solid fa-moon fs-8"></span>
                </label>
            </div>
        </li>
		<li class="nav-item">
            <a class="nav-link pt-3" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#searchBoxModal">
                <span class="fa-solid fa-search fs-8"></span>
            </a>
        </li>
		<li class="nav-item dropdown">
			<a class="nav-link lh-1 pe-0" id="navbarDropdownUser" href="#!" role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true" aria-expanded="false">
				<div class="avatar avatar-l ">
					<img class="rounded-circle " src="{{ urlPath($loggedUser->avatar) }}" alt="{{ $loggedUser->fullname }}" />
				</div>
			</a>
			<div class="dropdown-menu dropdown-menu-end navbar-dropdown-caret py-0 dropdown-profile shadow border" aria-labelledby="navbarDropdownUser">
				<div class="card position-relative border-0">
					<div class="card-body p-1">
                        <div class="overflow-auto scrollbar" style="max-height: 10rem;">
                            <ul class="nav d-flex flex-column">

                                <!-- Profile -->
                                <li class="nav-item">
                                    <a class="nav-link px-3 d-block" href="@route('app.profile')">
                                        <span class="me-2 text-body align-bottom" data-feather="user"></span>
                                        <span>Profile</span>
                                    </a>
                                </li>

                                <!-- Intergration -->
                                @if(SurveyrConfig('api.enabled') && $loggedUser->role == 'admin')
                                    <li class="nav-item">
                                        <a class="nav-link px-3 d-block" href="@route('intergration.setup')">
                                            <span class="me-2 text-body align-bottom" data-feather="link"></span>
                                            <span>Integrations</span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </div>
					</div>
					<div class="card-footer p-0 border-top border-translucent pt-3">
						<div class="px-3">
                            <a class="btn btn-phoenix-secondary d-flex flex-center w-100" href="@route('logout')">
                                <span class="me-2" data-feather="log-out"> </span>Sign out
                            </a>
                        </div>

						<div class="my-2 text-center fw-bold fs-10 text-body-quaternary">
                            <a class="text-body-quaternary me-1" href="@route('policy.privacy')">Privacy policy</a>&bull;
                            <a class="text-body-quaternary mx-1" href="@route('policy.terms')">Terms</a>
                        </div>
					</div>
				</div>
			</div>
		</li>
	</ul>
</nav>