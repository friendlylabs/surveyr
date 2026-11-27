{{--
    The install's settings (Admin\SettingsController). Storage: where uploaded files are kept, and that storage's
    details (pages/admin-settings.js shows the chosen one's fields and runs Test connection).
--}}
<x-layout-app :title="__('Settings')">
    <form method="POST" action="{{ route('admin.settings.storage') }}" onsubmit="submitForm(event)" novalidate data-storage-form data-test-url="{{ route('admin.settings.storage.test') }}">
        <x-section id="storageSettings" :title="__('Storage')" split
            :description="__('Where files respondents upload are kept. They\'re never public: people with access to a form see them through links that last a few minutes. Switching moves nothing; earlier files stay where they were uploaded.')">
            <div class="flex max-w-120 flex-col gap-5">
                <div>
                    <x-form.select id="driver" name="driver" :label="__('Storage')" :options="collect($storages)->map(fn ($storage) => $storage[0])->all()" :selected="$driver" :search="false" data-storage-driver />
                    @foreach ($storages as $key => [$name, $hint])
                        <p class="pc-field-hint" data-storage-hint="{{ $key }}" @if ($driver !== $key) hidden @endif>{{ $hint }}</p>
                    @endforeach
                </div>

                {{-- secrets are never shown again: a placeholder says one is saved, and leaving it empty keeps it --}}
                <div class="flex flex-col gap-4" data-storage-fields="s3" @if ($driver !== 's3') hidden @endif>
                    <x-form.input id="s3Key" name="s3_key" :label="__('Access key ID')" :value="$s3['key'] ?? ''" autocomplete="off" required />
                    <x-form.input id="s3Secret" name="s3_secret" type="password" :label="__('Secret access key')" autocomplete="new-password"
                        :placeholder="$s3HasSecret ? __('Saved. Leave empty to keep it.') : ''" />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-form.input id="s3Bucket" name="s3_bucket" :label="__('Bucket')" :value="$s3['bucket'] ?? ''" autocomplete="off" required />
                        <x-form.input id="s3Region" name="s3_region" :label="__('Region')" :value="$s3['region'] ?? ''" autocomplete="off" placeholder="us-east-1" />
                    </div>
                    <x-form.input id="s3Endpoint" name="s3_endpoint" type="url" :label="__('Endpoint')" :value="$s3['endpoint'] ?? ''" autocomplete="off"
                        placeholder="https://s3.example.com" :hint="__('Only for S3-compatible stores other than Amazon S3. Empty: Amazon S3.')" />
                    <label class="pc-check">
                        <input type="checkbox" name="s3_path_style" value="1" @checked($s3['path_style'] ?? false)>
                        {{ __('Path-style addresses (bucket in the path, as MinIO needs)') }}
                    </label>
                </div>

                <div class="flex flex-col gap-4" data-storage-fields="r2" @if ($driver !== 'r2') hidden @endif>
                    <x-form.input id="r2Account" name="r2_account" :label="__('Account ID')" :value="$r2['account'] ?? ''" autocomplete="off" required
                        :hint="__('In the Cloudflare dashboard, under R2: the ID in your account\'s S3 API address.')" />
                    <x-form.input id="r2Key" name="r2_key" :label="__('Access key ID')" :value="$r2['key'] ?? ''" autocomplete="off" required />
                    <x-form.input id="r2Secret" name="r2_secret" type="password" :label="__('Secret access key')" autocomplete="new-password"
                        :placeholder="$r2HasSecret ? __('Saved. Leave empty to keep it.') : ''" />
                    <x-form.input id="r2Bucket" name="r2_bucket" :label="__('Bucket')" :value="$r2['bucket'] ?? ''" autocomplete="off" required />
                </div>

                <p class="text-xs text-pc-muted">{{ __('Files up to :mb MB each (SURVEYR_UPLOADS_MAX_KB).', ['mb' => $maxMb]) }}</p>

                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="pc-btn pc-btn-primary">{{ __('Save storage') }}</button>
                    <button type="button" class="pc-btn pc-btn-secondary" data-storage-test>{{ __('Test connection') }}</button>
                </div>
            </div>
        </x-section>
    </form>

    @push('body')
        <script src="@asset('assets/js/pages/admin-settings.js')" defer></script>
    @endpush
</x-layout-app>
