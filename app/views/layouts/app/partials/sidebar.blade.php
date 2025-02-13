<div class="collapse navbar-collapse navbar-top-collapse order-1 order-lg-0 justify-content-center" id="navbarTopCollapse">
    <ul class="navbar-nav navbar-nav-top" data-dropdown-on-hover="data-dropdown-on-hover">
        <li class="nav-item">
            <a class="nav-link lh-1" href="@route('app.home')">
                <i class="fa-solid fa-grid-2 me-2 fs-10"></i>
                Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link lh-1 @active('app.spaces')" href="@route('spaces.list')">
                <i class="fa-solid fa-folders me-2 fs-10"></i>
                Spaces
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link lh-1 @active('app.zones')" href="@route('zones.list')">
                <i class="fa-solid fa-server me-2 fs-10"></i>
                Zones
            </a>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link lh-1 @active('app.forms')" href="@route('forms.list')">
                <span class="fa-regular fa-table-tree me-2 fs-10"></span>
                Forms
            </a>
        </li>
    </ul>
</div>