{{--
    The components reference (ComponentsController): every building block of the design system, live, with the markup
    or call that renders it. Classes are in resources/css/app.css; behaviour in public/assets/js/pc-*.js; Blade
    components in resources/views/components. Delete this page once the team knows the kit.
--}}
<x-layout-app :title="__('Components')">
    <div class="flex flex-col pb-6">

        {{-- ============ Buttons ============ --}}
        <section id="buttons" class="pc-section" aria-labelledby="buttonsTitle">
            <h2 id="buttonsTitle" class="pc-section-title mb-4">Buttons <code class="pc-badge pc-badge-mono pc-badge-neutral">.pc-btn</code></h2>
            <div class="flex flex-col gap-5">
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" class="pc-btn pc-btn-primary">Primary</button>
                    <button type="button" class="pc-btn pc-btn-brand">Brand</button>
                    <button type="button" class="pc-btn pc-btn-secondary">Secondary</button>
                    <button type="button" class="pc-btn pc-btn-outline">Outline</button>
                    <button type="button" class="pc-btn pc-btn-ghost">Ghost</button>
                    <button type="button" class="pc-btn pc-btn-success">Success</button>
                    <button type="button" class="pc-btn pc-btn-info">Info</button>
                    <button type="button" class="pc-btn pc-btn-danger">Danger</button>
                    <button type="button" class="pc-btn pc-btn-soft-danger">Soft danger</button>
                    <button type="button" class="pc-btn pc-btn-danger-solid">Danger solid</button>
                    <button type="button" class="pc-btn pc-btn-secondary" disabled>Disabled</button>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" class="pc-btn pc-btn-primary pc-btn-sm"><i class="fa-regular fa-plus" aria-hidden="true"></i>Small</button>
                    <button type="button" class="pc-btn pc-btn-primary"><i class="fa-regular fa-plus" aria-hidden="true"></i>Default</button>
                    <button type="button" class="pc-btn pc-btn-primary pc-btn-lg"><i class="fa-regular fa-plus" aria-hidden="true"></i>Large</button>
                    <button type="button" class="pc-btn pc-btn-secondary pc-btn-icon" aria-label="Settings" data-pc-tooltip="Icon button"><i class="fa-regular fa-gear text-base" aria-hidden="true"></i></button>
                    <button type="button" class="pc-btn pc-btn-ghost pc-btn-icon pc-btn-sm" aria-label="Edit" data-pc-tooltip="Small icon"><i class="fa-regular fa-pen text-base" aria-hidden="true"></i></button>
                    <button type="button" class="pc-btn pc-btn-secondary" aria-pressed="false" onclick="this.setAttribute('aria-pressed', this.getAttribute('aria-pressed') !== 'true')"><i class="fa-regular fa-eye" aria-hidden="true"></i>Toggle (aria-pressed)</button>
                    <button type="button" class="pc-btn pc-btn-secondary" onclick="demoLoading(this)">buttonState(btn, 'loading')</button>
                    <span class="pc-close" aria-hidden="true">×</span>
                </div>
            </div>
        </section>

        {{-- ============ Feedback ============ --}}
        <section id="feedback" class="pc-section" aria-label="Feedback">
            <div class="grid grid-cols-2 gap-x-12 gap-y-10 max-lg:grid-cols-1">
            <div>
                <h2 class="pc-section-title mb-4">Badges, dots &amp; status</h2>
                <div class="flex flex-col gap-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="pc-badge pc-badge-neutral">Neutral</span>
                        <span class="pc-badge pc-badge-success">Success</span>
                        <span class="pc-badge pc-badge-warning">Warning</span>
                        <span class="pc-badge pc-badge-danger">Danger</span>
                        <span class="pc-badge pc-badge-info">Info</span>
                        <span class="pc-badge pc-badge-brand">Brand</span>
                        <span class="pc-badge pc-badge-dark">Dark</span>
                        <span class="pc-badge pc-badge-mono pc-badge-neutral">INV-0042</span>
                        <span class="pc-pill">12</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-4 text-[13px]">
                        <span class="pc-legend"><span class="pc-dot pc-dot-success"></span>Online</span>
                        <span class="pc-legend"><span class="pc-dot pc-dot-warning"></span>Away</span>
                        <span class="pc-legend"><span class="pc-dot pc-dot-danger"></span>Offline</span>
                        <span class="pc-legend"><span class="pc-dot pc-dot-info"></span>Syncing</span>
                        <span class="pc-live">Live</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-4">
                        <span class="pc-status pc-status-success">PAID</span>
                        <span class="pc-status pc-status-warning">PENDING</span>
                        <span class="pc-status pc-status-danger">FAILED</span>
                        <span class="pc-eyebrow">Eyebrow label</span>
                        <span class="pc-mono text-[13px]">1,204.50</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-avatar name="Ada Lovelace" size="sm" />
                        <x-avatar name="Grace Hopper" />
                        <x-avatar name="Alan Turing" size="lg" />
                        <span class="pc-avatar pc-avatar-square">KJ</span>
                        <span class="text-xs text-pc-muted">&lt;x-avatar name="…" size="sm|lg" /&gt;</span>
                    </div>
                </div>
            </div>

            <div>
                <h2 class="pc-section-title mb-4">Alerts</h2>
                <div class="flex flex-col gap-2.5">
                    <div class="pc-alert pc-alert-info" role="status"><strong>Heads up.</strong> Maintenance is scheduled for Sunday night.</div>
                    <div class="pc-alert pc-alert-success" role="status">Your changes were saved.</div>
                    <div class="pc-alert pc-alert-warning" role="alert">Your trial ends in 3 days.</div>
                    <div class="pc-alert pc-alert-danger flex items-center gap-3" role="alert">
                        <i class="fa-regular fa-octagon-exclamation pc-alert-icon text-base" aria-hidden="true"></i>
                        <span class="flex-1">The import failed on row 14.</span>
                        <button type="button" class="pc-btn pc-btn-soft-danger pc-btn-sm">Retry</button>
                    </div>
                </div>
            </div>

            <div>
                <h2 class="pc-section-title mb-4">Progress, spinner &amp; skeleton</h2>
                <div class="flex flex-col gap-4">
                    <div class="pc-progress" role="progressbar" aria-valuenow="64" aria-valuemin="0" aria-valuemax="100" aria-label="Upload"><div class="pc-progress-bar" style="width:64%"></div></div>
                    <div class="pc-progress pc-progress-ink" role="progressbar" aria-valuenow="35" aria-valuemin="0" aria-valuemax="100" aria-label="Processing"><div class="pc-progress-bar" style="width:35%"></div></div>
                    <div class="pc-progress-stack" aria-label="Storage: 45% documents, 20% media, 10% other">
                        <span class="bg-pc-brand" style="width:45%"></span><span class="bg-pc-info" style="width:20%"></span><span class="bg-pc-warning" style="width:10%"></span>
                    </div>
                    <div class="flex items-center gap-3 text-[13px] text-pc-muted"><span class="pc-spinner"></span>Loading…</div>
                    <div class="flex flex-col gap-2">
                        <div class="pc-skeleton h-3 w-2/3"></div>
                        <div class="pc-skeleton h-3 w-1/2"></div>
                        <div class="pc-skeleton h-3 w-3/4"></div>
                    </div>
                </div>
            </div>

            <div>
                <h2 class="pc-section-title mb-4">Page loader <code class="pc-badge pc-badge-mono pc-badge-neutral">x-page-loader</code></h2>
                <div class="flex flex-col items-center gap-4 py-8">
                    <x-page-loader :overlay="false" />
                    <button type="button" class="pc-btn pc-btn-secondary pc-btn-sm" onclick="pcPageLoader.show(); setTimeout(pcPageLoader.hide, 3000)">Preview full page</button>
                </div>
            </div>

            <div>
                <h2 class="pc-section-title mb-4">Empty state <code class="pc-badge pc-badge-mono pc-badge-neutral">x-empty</code></h2>
                <x-empty icon="folder-open" title="No projects yet">
                    <div class="max-w-xs">Projects you create show up here.</div>
                </x-empty>
            </div>
            </div>
        </section>

        <section id="sections" class="pc-section" aria-labelledby="sectionsTitle">
            <h2 id="sectionsTitle" class="pc-section-title">Sections &amp; cards <code class="pc-badge pc-badge-mono pc-badge-neutral">x-section · .pc-card</code></h2>
            <p class="pc-section-desc mb-4">Parts of a page are an x-section: a title over the content, no box, a hairline between sections (split for settings pages). Keep .pc-card for things that stand alone, like gallery items and placeholders.</p>
            <div class="grid grid-cols-3 gap-4 max-lg:grid-cols-1">
                <div class="pc-card p-4">
                    <div class="text-sm font-semibold text-pc-ink">Customer feedback</div>
                    <div class="mt-1 text-xs text-pc-muted">Template · 8 questions</div>
                </div>
                <div class="pc-card p-4">
                    <div class="text-sm font-semibold text-pc-ink">Event registration</div>
                    <div class="mt-1 text-xs text-pc-muted">Template · 12 questions</div>
                </div>
                <div class="pc-card-dashed grid place-items-center p-4 text-xs text-pc-muted">Drop a template here</div>
            </div>
        </section>

        <section class="pc-section" aria-label="Stat tiles">
            <h2 class="pc-section-title mb-4">Stats <code class="pc-badge pc-badge-mono pc-badge-neutral">x-stat</code></h2>
            <div class="pc-stat-grid">
            <x-stat icon="dollar-sign" tone="ink" label="Revenue" value="$48.2k">+12% on last month</x-stat>
            <x-stat icon="cart-shopping" tone="brand" label="Orders" value="1,204" :href="route('components')">Linked: the whole tile is a link</x-stat>
            <x-stat icon="triangle-exclamation" tone="danger" label="Failed payments" value="7" />
            <x-stat icon="clock" tone="warning" label="Avg. response" value="2.4h">tone = ink | brand | success | warning | danger</x-stat>
            </div>
        </section>

        {{-- ============ Forms ============ --}}
        <section id="forms" class="pc-section" aria-labelledby="formsTitle">
            <h2 id="formsTitle" class="pc-section-title mb-4">
                Forms <span class="text-xs font-normal text-pc-muted">x-form.input · x-form.select · x-form.textarea · x-form.checklist · x-form.date</span>
            </h2>
            {{-- action="#" + a responseHandler: posts nowhere, just shows the inline-error contract --}}
            <form id="demoForm" class="grid grid-cols-2 gap-x-6 gap-y-4 max-md:grid-cols-1" novalidate>
                <x-form.input id="demoName" name="name" label="Project name" placeholder="Apollo" required hint="Shown on invoices and emails." />
                <x-form.input id="demoBudget" name="budget" type="number" label="Budget" placeholder="0.00" class="pc-input-mono" />
                <x-form.select id="demoTeam" name="team" label="Team (single, searchable)" :options="$options" placeholder="Choose a team" required />
                <x-form.select id="demoTeams" name="teams" label="Teams (multiple)" :options="$options" :selected="['design', 'engineering']" multiple placeholder="Choose teams" />
                <x-form.textarea id="demoNotes" name="notes" label="Notes" rows="3" placeholder="Anything we should know?" />
                <x-form.checklist id="demoChannels" name="channels" label="Notify by" :options="['email' => 'Email', 'sms' => 'SMS', 'slack' => 'Slack']" :selected="['email']" />
                <x-form.date id="demoStartsOn" name="starts_on" label="Start date" :min="now()" required hint="Today or later." />
                <x-form.date id="demoOpensAt" name="opens_at" label="Opens at (time)" mode="time" value="09:00" :step="15" />
                <x-form.date id="demoKickoff" name="kickoff_at" label="Kickoff (date & time)" mode="datetime" :value="now()->addDay()->setTime(10, 30)" />
                <x-form.date id="demoPeriod" name="from" end-name="to" label="Period (range with shortcuts)" range :value="now()->subDays(6)" :end-value="now()" />

                <div>
                    <span class="pc-label">Search input group</span>
                    <span class="pc-input-group"><i class="fa-regular fa-magnifying-glass text-base" aria-hidden="true"></i><input class="pc-input" type="search" placeholder="Search…" aria-label="Search"></span>
                </div>
                <div data-pc-pw-strength>
                    <label class="pc-label" for="demoPassword">Password with strength</label>
                    <span class="pc-input-group">
                        <input id="demoPassword" type="password" class="pc-input pe-16" data-pc-pw-input autocomplete="off">
                        <button type="button" class="pc-input-addon" data-pc-pw-toggle aria-label="Show password">SHOW</button>
                    </span>
                    <div class="pc-strength" aria-hidden="true"><span data-pc-strength-bar></span><span data-pc-strength-bar></span><span data-pc-strength-bar></span></div>
                </div>
                <div class="flex flex-wrap items-center gap-6">
                    <label class="pc-check"><input type="checkbox" checked> Checkbox</label>
                    <label class="pc-check"><input type="radio" name="demoRadio" checked> Radio A</label>
                    <label class="pc-check"><input type="radio" name="demoRadio"> Radio B</label>
                    <span class="inline-flex items-center gap-2 text-sm">
                        <button type="button" role="switch" aria-checked="true" data-pc-trigger="toggle" data-pc-on="true" class="pc-toggle" aria-label="Email alerts"><span class="pc-toggle-thumb"></span></button>
                        Toggle
                        <input type="checkbox" name="alerts" checked hidden data-pc-toggle-input>
                    </span>
                </div>
                <div>
                    <label class="pc-label" for="demoRange">Range</label>
                    <input id="demoRange" type="range" class="pc-range" value="60">
                </div>
                <x-form.input id="demoReadonly" name="reference" label="Read-only" value="REF-2026-0042" readonly />
                <x-form.input id="demoDisabled" name="disabled" label="Disabled" value="Not editable" disabled />

                <div class="col-span-full flex items-center justify-end gap-2 border-t border-pc-border-soft pt-4">
                    <span class="me-auto text-xs text-pc-muted">Submit to see the 422 inline errors that submitForm() places under each field.</span>
                    <button type="reset" class="pc-btn pc-btn-secondary">Reset</button>
                    <button type="submit" class="pc-btn pc-btn-primary">Validate</button>
                </div>
            </form>
        </section>

        {{-- ============ Overlays ============ --}}
        <section id="overlays" class="pc-section" aria-labelledby="overlaysTitle">
            <h2 id="overlaysTitle" class="pc-section-title mb-4">Overlays</h2>
            <div class="grid grid-cols-2 gap-6 max-lg:grid-cols-1">
                <div class="flex flex-col gap-2.5">
                    <div class="pc-eyebrow">Modal · x-dialog.modal · pc-modal.js</div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="pc-btn pc-btn-secondary" data-pc-trigger="modal" data-pc-target="#demoModalSm">Small</button>
                        <button type="button" class="pc-btn pc-btn-secondary" data-pc-trigger="modal" data-pc-target="#demoModal">Default</button>
                        <button type="button" class="pc-btn pc-btn-secondary" data-pc-trigger="modal" data-pc-target="#demoModalLg">Large</button>
                        <button type="button" class="pc-btn pc-btn-secondary" data-pc-trigger="modal" data-pc-target="#demoModalStatic">Static backdrop</button>
                    </div>
                </div>
                <div class="flex flex-col gap-2.5">
                    <div class="pc-eyebrow">Offcanvas · x-dialog.offcanvas · pc-offcanvas.js</div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="pc-btn pc-btn-secondary" data-pc-trigger="offcanvas" data-pc-target="#demoOffcanvas">From the right</button>
                        <button type="button" class="pc-btn pc-btn-secondary" data-pc-trigger="offcanvas" data-pc-target="#demoOffcanvasStart">From the left</button>
                        <button type="button" class="pc-btn pc-btn-secondary" data-pc-trigger="offcanvas" data-pc-target="#demoOffcanvasLg">Wide</button>
                    </div>
                </div>
                <div class="flex flex-col gap-2.5">
                    <div class="pc-eyebrow">Dialogs · pcDialog · data-pc-confirm</div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="pc-btn pc-btn-secondary" onclick="pcDialog.alert({ title: 'Saved', text: 'Your report is ready to download.', icon: 'success' })">Alert</button>
                        <button type="button" class="pc-btn pc-btn-secondary" onclick="demoConfirm()">Confirm</button>
                        <button type="button" class="pc-btn pc-btn-danger" data-pc-confirm="Archive this project?" data-pc-confirm-text="You can restore it later from Archived." data-pc-confirm-icon="warning" data-pc-confirm-button="Archive">data-pc-confirm</button>
                        <button type="button" class="pc-btn pc-btn-secondary" onclick="underDevelopment(event)">underDevelopment()</button>
                    </div>
                </div>
                <div class="flex flex-col gap-2.5">
                    <div class="pc-eyebrow">Toasts · toast.* · pcToast</div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="pc-btn pc-btn-secondary" onclick="toast.success({ message: 'Changes saved' })">Success</button>
                        <button type="button" class="pc-btn pc-btn-secondary" onclick="toast.error({ message: 'Couldn\'t save. Try again.' })">Error</button>
                        <button type="button" class="pc-btn pc-btn-secondary" onclick="toast.warning({ message: 'You\'re offline' })">Warning</button>
                        <button type="button" class="pc-btn pc-btn-secondary" onclick="toast.info({ message: 'New version available' })">Info</button>
                        <button type="button" class="pc-btn pc-btn-secondary" onclick="pcToast.show('Item deleted', { duration: 5000, action: { label: 'UNDO', onClick: () => toast.info({ message: 'Restored' }) } })">With action</button>
                    </div>
                </div>
                <div class="flex flex-col gap-2.5">
                    <div class="pc-eyebrow">Dropdown · pc-dropdown.js</div>
                    <div class="pc-dropdown">
                        <button type="button" class="pc-btn pc-btn-secondary" data-pc-trigger="dropdown" data-pc-target="#demoMenu" aria-haspopup="menu" aria-expanded="false">
                            Actions <i class="fa-regular fa-chevron-down text-xs" aria-hidden="true"></i>
                        </button>
                        <div id="demoMenu" class="pc-dropdown-menu" role="menu" aria-hidden="true">
                            <button type="button" class="pc-dropdown-item" role="menuitem" data-pc-dismiss="dropdown"><i class="fa-regular fa-pen text-base" aria-hidden="true"></i>Edit</button>
                            <button type="button" class="pc-dropdown-item pc-dropdown-item-active" role="menuitem" data-pc-dismiss="dropdown"><i class="fa-regular fa-star text-base" aria-hidden="true"></i>Starred</button>
                            <button type="button" class="pc-dropdown-item" role="menuitem" data-pc-dismiss="dropdown" onclick="copyToClipboard(location.href)"><i class="fa-regular fa-link text-base" aria-hidden="true"></i>Copy link</button>
                            <div class="pc-dropdown-divider"></div>
                            <button type="button" class="pc-dropdown-item pc-dropdown-item-danger" role="menuitem" data-pc-dismiss="dropdown"><i class="fa-regular fa-trash-can text-base" aria-hidden="true"></i>Delete</button>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col gap-2.5">
                    <div class="pc-eyebrow">Tooltip &amp; popover · pc-tooltip.js · pc-popover.js</div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" class="pc-btn pc-btn-secondary" data-pc-tooltip="Tooltip on top">Top</button>
                        <button type="button" class="pc-btn pc-btn-secondary" data-pc-tooltip="Tooltip on the right" data-pc-placement="right">Right</button>
                        <button type="button" class="pc-btn pc-btn-secondary" data-pc-trigger="popover" data-pc-target="#demoPopover" data-pc-placement="bottom-start" data-pc-hover aria-expanded="false">Popover (hover / click)</button>
                        <div id="demoPopover" class="pc-popover" role="dialog" aria-hidden="true">
                            <div class="text-[13px] font-semibold">Usage this month</div>
                            <p class="pc-popover-text">You've used 8,400 of 10,000 API calls. The count resets on the 1st.</p>
                            <div class="pc-progress"><div class="pc-progress-bar" style="width:84%"></div></div>
                            <div class="flex justify-end"><button type="button" class="pc-btn pc-btn-secondary pc-btn-sm" data-pc-dismiss="popover">Got it</button></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ============ Navigation ============ --}}
        <section id="navigation" class="pc-section" aria-labelledby="navigationTitle">
            <h2 id="navigationTitle" class="pc-section-title mb-4">Tabs <span class="text-xs font-normal text-pc-muted">pc-tabs.js · pc:tabchange</span></h2>
            <div class="flex flex-col gap-5">
                <div class="flex flex-wrap items-center gap-6">
                    <div data-pc-tabs-group class="pc-tabs" role="tablist" aria-label="Pill tabs">
                        <button type="button" data-pc-trigger="tabs" data-pc-value="all" class="pc-tab pc-tab-active" role="tab" aria-selected="true">All <span class="pc-tab-count">24</span></button>
                        <button type="button" data-pc-trigger="tabs" data-pc-value="open" class="pc-tab" role="tab" aria-selected="false" tabindex="-1">Open <span class="pc-tab-count">9</span></button>
                        <button type="button" data-pc-trigger="tabs" data-pc-value="closed" class="pc-tab" role="tab" aria-selected="false" tabindex="-1">Closed</button>
                    </div>
                    <div data-pc-tabs-group class="pc-segmented" role="tablist" aria-label="Segmented">
                        <button type="button" data-pc-trigger="tabs" data-pc-value="day" class="pc-tab pc-tab-active" role="tab" aria-selected="true">Day</button>
                        <button type="button" data-pc-trigger="tabs" data-pc-value="week" class="pc-tab" role="tab" aria-selected="false" tabindex="-1">Week</button>
                        <button type="button" data-pc-trigger="tabs" data-pc-value="month" class="pc-tab" role="tab" aria-selected="false" tabindex="-1">Month</button>
                    </div>
                    <div data-pc-tabs-group class="pc-tabs pc-tabs-soft" role="tablist" aria-label="Soft tabs">
                        <button type="button" data-pc-trigger="tabs" data-pc-value="list" class="pc-tab pc-tab-active" role="tab" aria-selected="true"><i class="fa-regular fa-list" aria-hidden="true"></i>List</button>
                        <button type="button" data-pc-trigger="tabs" data-pc-value="grid" class="pc-tab" role="tab" aria-selected="false" tabindex="-1"><i class="fa-regular fa-grid-2" aria-hidden="true"></i>Grid</button>
                    </div>
                </div>

                <div>
                    <div data-pc-tabs-group data-pc-tabs-panels="#demoPanels" class="pc-tabs-underline" role="tablist" aria-label="Underline tabs with panels">
                        <button type="button" data-pc-trigger="tabs" data-pc-value="overview" class="pc-tab pc-tab-active" role="tab" aria-selected="true">Overview</button>
                        <button type="button" data-pc-trigger="tabs" data-pc-value="activity" class="pc-tab" role="tab" aria-selected="false" tabindex="-1">Activity <span class="pc-tab-count">3</span></button>
                        <button type="button" data-pc-trigger="tabs" data-pc-value="settings" class="pc-tab" role="tab" aria-selected="false" tabindex="-1">Settings</button>
                    </div>
                    <div id="demoPanels" class="pt-4 text-[13px] text-pc-text-2">
                        <div data-pc-tab-panel="overview" data-pc-open="true">Underline tabs switch panels: give the group <code>data-pc-tabs-panels="#container"</code> and each panel <code>data-pc-tab-panel="value"</code>.</div>
                        <div data-pc-tab-panel="activity" data-pc-open="false">Three things happened while you were away.</div>
                        <div data-pc-tab-panel="settings" data-pc-open="false">Arrow keys move between tabs of a focused group.</div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ============ Data ============ --}}
        <section id="data" class="pc-section" aria-label="Data">
            {{-- x-table without a paginator: the footer slot becomes the card footer --}}
            <x-table label="Invoices">
                <x-slot:toolbar>
                    <span class="font-semibold">Table <span class="text-xs font-normal text-pc-muted">x-table · x-search · x-filter</span></span>
                    <x-search class="ms-auto" placeholder="Search invoices" />
                </x-slot:toolbar>
                <x-slot:head>
                    <th>Invoice</th><th>Customer</th><th>Status</th><th class="text-end">Amount</th><th class="w-20"><span class="sr-only">Actions</span></th>
                </x-slot:head>
                @foreach ([['INV-0042', 'Northwind', 'success', 'Paid', '1,204.50'], ['INV-0041', 'Contoso', 'warning', 'Pending', '860.00'], ['INV-0040', 'Fabrikam', 'danger', 'Overdue', '2,310.00'], ['INV-0039', 'Initech', 'success', 'Paid', '415.75']] as [$number, $customer, $tone, $status, $amount])
                    <tr>
                        <td class="font-pc-mono text-xs">{{ $number }}</td>
                        <td><div class="flex items-center gap-2.5"><x-avatar :name="$customer" size="sm" />{{ $customer }}</div></td>
                        <td><span class="pc-badge pc-badge-{{ $tone }}">{{ $status }}</span></td>
                        <td class="pc-num">{{ $amount }}</td>
                        <td class="text-end whitespace-nowrap">
                            <button type="button" class="pc-btn pc-btn-ghost pc-btn-icon pc-btn-sm" aria-label="Edit {{ $number }}" data-pc-tooltip="Edit" onclick="underDevelopment(event)"><i class="fa-regular fa-pen text-base" aria-hidden="true"></i></button>
                            <button type="button" class="pc-btn pc-btn-ghost pc-btn-icon pc-btn-sm text-pc-danger-strong" aria-label="Delete {{ $number }}" data-pc-tooltip="Delete"
                                data-pc-confirm="Delete {{ $number }}?" data-pc-confirm-text="This can't be undone." data-pc-confirm-icon="danger" data-pc-confirm-button="Delete"><i class="fa-regular fa-trash-can text-base" aria-hidden="true"></i></button>
                        </td>
                    </tr>
                @endforeach
                <x-slot:footer>
                    <span>4 invoices</span>
                    <span>Rows: <code>&lt;x-action.edit&gt;</code> / <code>&lt;x-action.delete&gt;</code> for real records</span>
                </x-slot:footer>
            </x-table>
        </section>
    </div>

    {{-- the overlays the buttons above open --}}
    <x-dialog.modal id="demoModalSm" title="Small modal" size="sm">
        <p class="text-[13px] text-pc-text-2">size="sm": 380px. For short confirmations with a little context.</p>
    </x-dialog.modal>
    <x-dialog.modal id="demoModal" title="Invite teammate" subtitle="With an action, the panel is a form posted by submitForm()." action="#" submit="Send invite">
        <x-form.input id="demoInviteEmail" name="email" type="email" label="Email" placeholder="name@company.com" required data-pc-autofocus />
        <x-form.select id="demoInviteTeam" name="team" label="Team" :options="$options" placeholder="Choose a team" />
    </x-dialog.modal>
    <x-dialog.modal id="demoModalLg" title="Large modal" subtitle="size = sm | lg | xl" size="lg">
        <div class="grid grid-cols-2 gap-4 max-sm:grid-cols-1">
            <x-form.input id="demoLgFirst" name="first_name" label="First name" />
            <x-form.input id="demoLgLast" name="last_name" label="Last name" />
            <div class="col-span-full"><x-form.textarea id="demoLgBio" name="bio" label="Bio" rows="4" /></div>
        </div>
        <x-slot:footer>
            <button type="button" class="pc-btn pc-btn-ghost me-auto" data-pc-dismiss="modal">Skip for now</button>
            <button type="button" class="pc-btn pc-btn-secondary" data-pc-dismiss="modal">Cancel</button>
            <button type="button" class="pc-btn pc-btn-primary" data-pc-dismiss="modal" onclick="toast.success({ message: 'Saved' })">Save</button>
        </x-slot:footer>
    </x-dialog.modal>
    <x-dialog.modal id="demoModalStatic" title="Static backdrop" data-pc-static>
        <p class="text-[13px] text-pc-text-2">data-pc-static: backdrop clicks and Escape are ignored; only the buttons close it.</p>
    </x-dialog.modal>

    <x-dialog.offcanvas id="demoOffcanvas" title="Northwind" subtitle="Customer since March 2024">
        <div class="flex items-center gap-3">
            <x-avatar name="Northwind Traders" size="lg" />
            <div><div class="font-semibold">Northwind Traders</div><div class="text-xs text-pc-muted">billing@northwind.test</div></div>
        </div>
        <dl class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-6 gap-y-2 text-[13px]">
            <dt class="text-pc-muted">Plan</dt><dd>Business</dd>
            <dt class="text-pc-muted">Seats</dt><dd>24 of 30</dd>
            <dt class="text-pc-muted">Status</dt><dd><span class="pc-badge pc-badge-success">Active</span></dd>
        </dl>
        <p class="text-[13px] text-pc-muted">Offcanvas panels hold record details and long forms without taking the reader off the page.</p>
    </x-dialog.offcanvas>
    <x-dialog.offcanvas id="demoOffcanvasStart" title="Filters" class="pc-offcanvas-start">
        <x-form.select id="demoFilterTeam" name="team" label="Team" :options="$options" multiple placeholder="Any team" />
        <x-form.checklist id="demoFilterStatus" name="status" label="Status" :options="['paid' => 'Paid', 'pending' => 'Pending', 'overdue' => 'Overdue']" />
        <x-slot:footer>
            <button type="button" class="pc-btn pc-btn-secondary" data-pc-dismiss="offcanvas">Clear</button>
            <button type="button" class="pc-btn pc-btn-primary" data-pc-dismiss="offcanvas">Apply filters</button>
        </x-slot:footer>
    </x-dialog.offcanvas>
    <x-dialog.offcanvas id="demoOffcanvasLg" title="Wide panel" subtitle="size = lg: 560px" size="lg">
        <div class="pc-hatch h-64">Preview area</div>
    </x-dialog.offcanvas>

    @push('body')
        <script src="@asset('assets/js/pc-tabs.js')" defer></script>
        <script src="@asset('assets/js/pc-toggle.js')" defer></script>
        <script src="@asset('assets/js/pc-popover.js')" defer></script>
        <script>
            function demoLoading(button) {
                buttonState(button, "loading");
                setTimeout(() => buttonState(button, "reset"), 1500);
            }

            function demoConfirm() {
                pcDialog.confirm({ title: "Discard changes?", text: "Your edits to this draft will be lost.", icon: "warning", confirmButtonText: "Discard" })
                    .then(({ isConfirmed }) => toast.info({ message: isConfirmed ? "Discarded" : "Kept editing" }));
            }

            document.addEventListener("DOMContentLoaded", () => {
                // the demo forms post nowhere: fake the 422 a controller's $request->validate() returns, through fieldError()
                const fake = (form, errors) => (e) => {
                    e.preventDefault();
                    clearFormErrors(form);
                    const bad = Object.entries(errors).filter(([name]) => !form.elements[name]?.value);
                    bad.forEach(([name, message]) => fieldError(form, name, message));
                    if (bad.length) return form.querySelector('[aria-invalid="true"]').focus();
                    toast.success({ message: "Looks good" });
                    if (window.pcModal) pcModal.close(form.closest(".pc-modal")?.id);
                };
                const demo = document.getElementById("demoForm");
                demo.addEventListener("submit", fake(demo, { name: "Give the project a name.", team: "Choose a team.", starts_on: "Choose a start date." }));
                demo.addEventListener("reset", () => clearFormErrors(demo));
                const invite = document.querySelector("#demoModal form");
                invite.removeAttribute("onsubmit");
                invite.addEventListener("submit", fake(invite, { email: "Enter an email address." }));
            });
        </script>
    @endpush
</x-layout-app>
