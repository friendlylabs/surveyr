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
                        <div class="overflow-auto scrollbar">
                            <ul class="nav d-flex flex-column pt-3">

                                <!-- Profile -->
                                <li class="nav-item">
                                    <a class="nav-link px-3 d-block" href="@route('app.profile')">
                                        <i class="fa-solid fa-gear me-2 fs-10"></i>
                                        <span>Account</span>
                                    </a>
                                </li>

                                <!-- Link Device -->
                                @if(SurveyrConfig('api.enabled'))
                                    <li class="nav-item">
                                        <a class="nav-link px-3 d-block" href="javascript:void(0)" id="linkDeviceBtn">
                                            <i class="fa-solid fa-qrcode me-2 fs-10"></i>
                                            <span>Link Device</span>
                                        </a>
                                    </li>
                                @endif

                                <hr class="p-0">

                                <!-- Intergration -->
                                @if(SurveyrConfig('api.enabled') && in_array($loggedUser->role, ['admin', 'moderator']))
                                    <li class="nav-item">
                                        <a class="nav-link px-3 d-block" href="@route('intergration.setup')">
                                            <i class="fa-solid fa-plug me-2 fs-10"></i>
                                            <span>Integrations</span>
                                        </a>
                                    </li>
                                @endif

                                <!-- User Management -->
                                @if(in_array($loggedUser->role, ['admin', 'moderator']))
                                    <li class="nav-item">
                                        <a class="nav-link px-3 d-block" href="@route('users.index')">
                                            <i class="fa-solid fa-users me-2 fs-10"></i>
                                            <span>User Management</span>
                                        </a>
                                    </li>
                                @endif
                                
                            </ul>
                        </div>
					</div>
					<div class="card-footer border-0 p-0 pt-3">
						<div class="px-3">
                            <a class="btn btn-phoenix-danger d-flex flex-center w-100" href="@route('logout')">
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

<!-- link device modal -->
<div class="modal fade" id="linkDeviceModal" tabindex="-1" aria-labelledby="linkDeviceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="linkDeviceModalLabel">Link Device</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pb-5">
                <div id="linkDeviceQrCode" class="text-center mx-auto rounded-4 p-3"
                    style="border: 4px solid #368d7b; width: fit-content;"><i class="fa fa-spinner fa-spin"></i></div>
            </div>
        </div>
    </div>
</div>

<script>
    let qrCodeInterval;
    document.getElementById('linkDeviceBtn').addEventListener('click', async function() {
        var QrCodeContainer = document.getElementById('linkDeviceQrCode');
        if (QrCodeContainer.innerHTML == '<i class="fa fa-spinner fa-spin"></i>') {
            generateAuthQrcode();
        }

        $('#linkDeviceModal').modal('show');

        // Register interval only if it hasn't been set
        if (!qrCodeInterval) {
            qrCodeInterval = setInterval(function() {
                generateAuthQrcode();
            }, 15000);
        }
    });

    async function generateAuthQrcode() {
        let authQrcodeUrl = "{{ request()->getUrl() }}{{ route('auth.scan', ':code') }}"
        $.ajax({
            url: '@route('device.code')',
            type: 'GET',
            success: function(response) {
                if(response.status) {
                    document.getElementById('linkDeviceQrCode').innerHTML = '';
                    authQrcodeUrl = authQrcodeUrl.replace(':code', response.code);
                    var qrcode = new QRCode(document.getElementById("linkDeviceQrCode"), {
                        width : 200,
                        height : 200,    
                        colorDark : "#1e6b5b",
                        colorLight : "#ffffff",
                    });

                    qrcode.makeCode(authQrcodeUrl);
                }else{
                    toast.error({message: response.message ?? 'Could not generate QR code'});
                }
            },error: function() {
                toast.error({message: 'Could not generate QR code'});
            }
        });
    }
</script>