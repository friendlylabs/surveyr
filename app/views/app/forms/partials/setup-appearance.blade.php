<div class="card d-none setup-card bg-transparent border-0" id="appearanceSettings">
    <div class="card-header py-3">
        <h5 class="card-title mb-0">Theme Settings</h5>
    </div>

    <div class="card-body p-1 pt-3">
        <div class="row">
            @foreach ($themes as $theme)
                <div 
                    class="col-md-6 col-lg-4 col-xxl-3 col-12 mb-3">
                    <div 
                        class="card theme-card {{ $form->theme == $theme->name ? 'bg-danger' : '' }}" 
                        data-theme="{{ $theme->theme }}">
                        <div class="card-body p-2">
                            <div class="theme-preview">
                                <img src="{{ $theme->cover }}" alt="{{ $theme->name }}" class="ratio ratio-3x4 mb-2 rounded-2">
                                <div class="theme-preview-content">
                                    <div class="theme-preview-title fw-bold">{{ $theme->name }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>