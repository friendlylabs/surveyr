{{--
    Top bar for the vertical layout. The logo column is the same width as the
    sidebar (Phoenix sets .navbar-top .navbar-logo to the sidebar width), so the
    page title starts flush with the content area, like the reference design.
--}}
<nav class="navbar navbar-top fixed-top navbar-expand" id="navbarDefault" style="border-bottom: 0.5px dashed var(--phoenix-border-color);">
    <div class="collapse navbar-collapse justify-content-between">
        <div class="navbar-logo">
            <button class="btn navbar-toggler navbar-toggler-humburger-icon hover-bg-transparent" type="button"
                onclick="menuToOffcanvas()" aria-label="Toggle navigation">
                <span class="navbar-toggle-icon">
                    <span class="toggle-line"></span>
                </span>
            </button>

            <a class="navbar-brand me-1 me-sm-3" href="@route('app.home')">
                <div class="d-flex align-items-center">
                    <img class="navbar-brand-logo d-dark-none" src="/assets/images/brand/logo-dark.png" alt="{{ _env('APP_NAME') }}" height="34" />
                    <img class="navbar-brand-logo d-light-none" src="/assets/images/brand/logo-light.png" alt="{{ _env('APP_NAME') }}" height="34" />
                    <img class="navbar-brand-icon" src="/assets/images/brand/logo-sm.png" alt="{{ _env('APP_NAME') }}" height="30" />
                </div>
            </a>
        </div>

        <div class="navbar-page-title d-none d-md-flex align-items-center flex-grow-1 min-w-0">
            <h1 class="navbar-page-title-text mb-0 text-truncate">{{ $title ?? 'Dashboard' }}</h1>
        </div>

        <ul class="navbar-nav navbar-nav-icons flex-row align-items-center">

            {{-- global search (opens the search modal) --}}
            <li class="nav-item d-none d-lg-block me-2">
                <button class="navbar-search-trigger" type="button" data-bs-toggle="modal" data-bs-target="#searchBoxModal">
                    <i class="ph-duotone ph-magnifying-glass"></i>
                    <span class="navbar-search-trigger-label">Search</span>
                    <kbd class="navbar-search-trigger-kbd">⌘ K</kbd>
                </button>
            </li>
            <li class="nav-item d-lg-none">
                <a class="nav-link navbar-icon-btn" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#searchBoxModal" aria-label="Search">
                    <i class="ph-duotone ph-magnifying-glass"></i>
                </a>
            </li>

            {{-- theme toggle --}}
            <li class="nav-item">
                <div class="theme-control-toggle">
                    <input class="form-check-input ms-0 theme-control-toggle-input" type="checkbox" data-theme-control="phoenixTheme" value="dark" id="themeControlToggle">
                    <label class="mb-0 theme-control-toggle-label theme-control-toggle-light navbar-icon-btn" for="themeControlToggle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Switch theme">
                        <i class="ph-duotone ph-sun"></i>
                    </label>
                    <label class="mb-0 theme-control-toggle-label theme-control-toggle-dark navbar-icon-btn" for="themeControlToggle" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="Switch theme">
                        <i class="ph-duotone ph-moon"></i>
                    </label>
                </div>
            </li>

            {{-- quick create (modals live in layouts.app.partials.create) --}}
            <li class="nav-item dropdown ms-1 ms-sm-2">
                <button class="btn btn-dark btn-sm navbar-new-btn" type="button" id="navbarNewMenu" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ph-duotone ph-squares-four"></i>
                    <span class="d-none d-sm-inline ms-1">Quick Actions</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end navbar-new-menu shadow border" aria-labelledby="navbarNewMenu">
                    <li class="pt-1">
                        <a class="dropdown-item" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#createFormModal">
                            <i class="ph-duotone ph-rows"></i>
                            <span>New form</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#createReportModal">
                            <i class="ph-duotone ph-chart-bar"></i>
                            <span>New report</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#createSpaceModal">
                            <i class="ph-duotone ph-folders"></i>
                            <span>New space</span>
                        </a>
                    </li>
                    <li class="pb-1">
                        <a class="dropdown-item" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#createZoneModal">
                            <i class="ph-duotone ph-database"></i>
                            <span>New zone</span>
                        </a>
                    </li>
                </ul>
            </li>

            {{-- account --}}
            <li class="nav-item dropdown ms-2">
                <a class="nav-link lh-1 p-0" id="navbarDropdownUser" href="#!" role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true" aria-expanded="false">
                    <div class="avatar avatar-l">
                        <img class="rounded-circle" src="{{ urlPath($loggedUser->avatar) }}" alt="{{ $loggedUser->fullname }}" />
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end navbar-dropdown-caret py-0 dropdown-profile shadow border" aria-labelledby="navbarDropdownUser">
                    <div class="card position-relative border-0">
                        <div class="card-body p-0">
                            <div class="d-flex align-items-center gap-2 px-3 pt-3 pb-2">
                                <div class="avatar avatar-l">
                                    <img class="rounded-circle" src="{{ urlPath($loggedUser->avatar) }}" alt="{{ $loggedUser->fullname }}" />
                                </div>
                                <div class="min-w-0">
                                    <h6 class="mb-0 fw-bold text-truncate">{{ $loggedUser->fullname }}</h6>
                                    <small class="text-body-tertiary text-truncate d-block">{{ $loggedUser->email }}</small>
                                </div>
                            </div>
                            <hr class="my-1">
                            <ul class="nav d-flex flex-column pb-2">
                                <li class="nav-item">
                                    <a class="nav-link px-3 d-block" href="@route('app.profile')">
                                        <i class="ph-duotone ph-user-circle me-2"></i>
                                        <span>Account</span>
                                    </a>
                                </li>
                                @if(SurveyrConfig('api.enabled'))
                                    <li class="nav-item">
                                        <a class="nav-link px-3 d-block" href="javascript:void(0)" id="linkDeviceBtn">
                                            <i class="ph-duotone ph-qr-code me-2"></i>
                                            <span>Link Device</span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                        <div class="card-footer border-0 p-0 pt-2">
                            <div class="px-3">
                                <a class="btn btn-phoenix-danger d-flex flex-center w-100" href="@route('logout')">
                                    <i class="ph-duotone ph-sign-out me-2"></i>Sign out
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
    </div>
</nav>

@if(SurveyrConfig('api.enabled'))
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
@endif
