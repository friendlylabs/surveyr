<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\FileStorage;
use App\Support\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * SettingsController
 *
 * The install's settings (admin/settings): for now, where uploaded files are kept (FileStorage): on this server, in
 * Amazon S3 (or another S3-compatible store) or in Cloudflare R2. A storage's details are checked (a file written,
 * read back and deleted) before they're saved; saved secrets are never shown again, and leaving one empty keeps it.
 * Switching moves nothing: earlier files stay where they were uploaded.
 */
class SettingsController extends Controller
{
    /** The settings page. */
    public function index(): View
    {
        $this->driver = FileStorage::active();
        $this->storages = [
            'local' => [__('This server'), __('Kept on this server, in storage/app/private/uploads.')],
            's3' => [__('Amazon S3'), __('An S3 bucket, or any S3-compatible store (MinIO, Backblaze B2, DigitalOcean Spaces, Wasabi) through its endpoint.')],
            'r2' => [__('Cloudflare R2'), __('An R2 bucket, with an R2 API token\'s access key.')],
        ];
        // what's saved, secrets left out (only whether there is one)
        foreach (array_keys(FileStorage::FIELDS) as $driver) {
            $details = FileStorage::details($driver);
            $this->{$driver} = array_diff_key($details, array_flip(FileStorage::SECRETS));
            $this->{$driver.'HasSecret'} = filled($details['secret'] ?? null);
        }
        $this->maxMb = round(config('surveyr.uploads.max_kb') / 1024);

        return view('admin.settings.index', $this->data);
    }

    /** Saves the storage and its details, once they're checked to work. */
    public function updateStorage(Request $request): JsonResponse
    {
        [$driver, $details] = $this->storage($request);
        if ($error = FileStorage::test($driver, $details)) {
            return $this->jsonError(__('Couldn\'t use this storage: :error', ['error' => $error]), 422);
        }

        if ($driver !== 'local') {
            Settings::put("storage.{$driver}", $details);
        }
        Settings::put('storage.driver', $driver);
        FileStorage::forget();

        return $this->jsonSuccess(__('Storage saved. New uploads go to :storage.', ['storage' => $this->name($driver)]));
    }

    /** Checks the storage works with the details given, without saving them. */
    public function testStorage(Request $request): JsonResponse
    {
        [$driver, $details] = $this->storage($request);
        if ($error = FileStorage::test($driver, $details)) {
            return $this->jsonError(__('Couldn\'t use this storage: :error', ['error' => $error]), 422);
        }

        return $this->jsonSuccess(__('Connected: :storage works.', ['storage' => $this->name($driver)]));
    }

    /**
     * The storage asked for and its details (validated; an empty secret keeps the saved one).
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function storage(Request $request): array
    {
        $driver = $request->validate(['driver' => ['required', Rule::in(FileStorage::DRIVERS)]])['driver'];
        if ($driver === 'local') {
            return [$driver, []];
        }

        $saved = FileStorage::details($driver);
        $needsSecret = blank($saved['secret'] ?? null);
        $rules = match ($driver) {
            's3' => [
                's3_key' => ['required', 'string', 'max:255'],
                's3_secret' => [$needsSecret ? 'required' : 'nullable', 'string', 'max:255'],
                's3_region' => ['nullable', 'string', 'max:50', 'regex:/^[a-z0-9-]+$/'],
                's3_bucket' => ['required', 'string', 'max:255'],
                's3_endpoint' => ['nullable', 'url:https,http', 'max:255'],
                's3_path_style' => ['nullable', 'boolean'],
            ],
            'r2' => [
                'r2_account' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9]+$/'],
                'r2_key' => ['required', 'string', 'max:255'],
                'r2_secret' => [$needsSecret ? 'required' : 'nullable', 'string', 'max:255'],
                'r2_bucket' => ['required', 'string', 'max:255'],
            ],
        };
        $input = $request->validate($rules, [], [
            's3_key' => __('access key'), 's3_secret' => __('secret key'), 's3_region' => __('region'), 's3_bucket' => __('bucket'),
            's3_endpoint' => __('endpoint'), 'r2_account' => __('account ID'), 'r2_key' => __('access key'), 'r2_secret' => __('secret key'),
            'r2_bucket' => __('bucket'),
        ]);

        $details = [];
        foreach (FileStorage::FIELDS[$driver] as $field) {
            $details[$field] = $input["{$driver}_{$field}"] ?? null;
        }
        if ($driver === 's3') {
            $details['path_style'] = $request->boolean('s3_path_style');
        }
        $details['secret'] = filled($details['secret']) ? $details['secret'] : ($saved['secret'] ?? null);

        return [$driver, $details];
    }

    /** A storage's name, as the page shows it. */
    private function name(string $driver): string
    {
        return match ($driver) {
            's3' => __('Amazon S3'),
            'r2' => __('Cloudflare R2'),
            default => __('this server'),
        };
    }

    /** Registers the settings routes (inside the /admin/settings prefix, routes/admin.php). */
    public static function routes(): void
    {
        Route::get('/', [self::class, 'index'])->name('settings');
        Route::post('/storage', [self::class, 'updateStorage'])->name('settings.storage');
        Route::post('/storage/test', [self::class, 'testStorage'])->middleware('throttle:10,1')->name('settings.storage.test');
    }
}
