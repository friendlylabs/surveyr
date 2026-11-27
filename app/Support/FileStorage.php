<?php

namespace App\Support;

use Aws\Exception\AwsException;
use Closure;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * FileStorage
 *
 * Where uploaded files are kept, and how to reach them: on this server (local, storage/app/private/uploads), in an
 * Amazon S3 bucket (or any S3-compatible store, through its endpoint) or in a Cloudflare R2 bucket. An admin picks one
 * and enters its details in the app (Admin › Settings › Storage; stored encrypted in Settings), over the .env defaults
 * (config/surveyr.php `uploads`). New uploads go to the chosen one; each upload remembers where it went, so earlier
 * files stay readable after a switch, as long as that storage's details are kept. Nothing is public: files are seen
 * through short-lived signed links only.
 */
class FileStorage
{
    /** The storages, by key. */
    const DRIVERS = ['local', 's3', 'r2'];

    /** The details each storage takes, and which of them are secret (never shown back, kept when left empty). */
    const FIELDS = [
        's3' => ['key', 'secret', 'region', 'bucket', 'endpoint', 'path_style'],
        'r2' => ['account', 'key', 'secret', 'bucket'],
    ];

    const SECRETS = ['secret'];

    /** @var array<string, FilesystemAdapter> */
    private static array $disks = [];

    /** The storage new uploads go to. */
    public static function active(): string
    {
        $driver = Settings::get('storage.driver', config('surveyr.uploads.disk', 'local'));

        return in_array($driver, self::DRIVERS, true) ? $driver : 'local';
    }

    /** A storage's filesystem (the active one by default). */
    public static function disk(?string $driver = null): FilesystemAdapter
    {
        $driver ??= self::active();

        return self::$disks[$driver] ??= Storage::build(self::config($driver));
    }

    /** Stands in for test(), in tests: given the driver and details, returns null (works) or what went wrong. */
    private static ?Closure $tester = null;

    /** Whether fake() is in force (tests): the fake disks then stay through forget(). */
    private static bool $faked = false;

    /** Forgets the filesystems made, after the settings change. */
    public static function forget(): void
    {
        if (! self::$faked) {
            self::$disks = [];
        }
    }

    /**
     * Tests only: every storage becomes a fake local disk (S3 and R2 hand out made-up signed links), and test() answers
     * with the given callback (null: it works).
     */
    public static function fake(?Closure $tester = null): void
    {
        foreach (self::DRIVERS as $driver) {
            $disk = Storage::fake("uploads-{$driver}");
            if (self::signsUrls($driver)) {
                $disk->buildTemporaryUrlsUsing(fn (string $path, $expires, array $options) => "https://{$driver}.storage.test/{$path}?".http_build_query(['expires' => $expires->getTimestamp()] + $options));
            }
            self::$disks[$driver] = $disk;
        }
        self::$tester = $tester ?? fn () => null;
        self::$faked = true;
    }

    /**
     * A storage's details: those saved in the app, over the .env defaults.
     *
     * @return array<string, mixed>
     */
    public static function details(string $driver): array
    {
        return array_merge(config("surveyr.uploads.{$driver}", []), array_filter((array) Settings::get("storage.{$driver}", []), fn ($value) => $value !== null && $value !== ''));
    }

    /**
     * The filesystem config for a storage, from its details (or the given ones, to test them before they're saved).
     *
     * @param  array<string, mixed>|null  $details
     * @return array<string, mixed>
     */
    public static function config(string $driver, ?array $details = null): array
    {
        $details ??= $driver === 'local' ? [] : self::details($driver);

        return match ($driver) {
            's3' => array_filter([
                'driver' => 's3',
                'key' => $details['key'] ?? null,
                'secret' => $details['secret'] ?? null,
                'region' => ($details['region'] ?? null) ?: 'us-east-1',
                'bucket' => $details['bucket'] ?? null,
                'endpoint' => ($details['endpoint'] ?? null) ?: null,
                'use_path_style_endpoint' => (bool) ($details['path_style'] ?? false),
                'visibility' => 'private',
                'throw' => true,
            ], fn ($value) => $value !== null),
            'r2' => [
                'driver' => 's3',
                'key' => $details['key'] ?? null,
                'secret' => $details['secret'] ?? null,
                'region' => 'auto',
                'bucket' => $details['bucket'] ?? null,
                'endpoint' => 'https://'.($details['account'] ?? '').'.r2.cloudflarestorage.com',
                'use_path_style_endpoint' => true,
                'visibility' => 'private',
                'throw' => true,
            ],
            default => ['driver' => 'local', 'root' => storage_path('app/private/uploads'), 'visibility' => 'private', 'throw' => true],
        };
    }

    /**
     * Checks a storage works with the given details: writes a small file, reads it back and deletes it. Returns null
     * when it works, or what went wrong.
     *
     * @param  array<string, mixed>  $details
     */
    public static function test(string $driver, array $details): ?string
    {
        if (self::$tester) {
            return (self::$tester)($driver, $details);
        }

        $path = '.surveyr-check-'.Str::random(12);
        try {
            $disk = Storage::build(self::config($driver, $driver === 'local' ? null : $details));
            $disk->put($path, 'ok');
            $read = $disk->get($path);
            $disk->delete($path);

            return $read === 'ok' ? null : __("The file written couldn't be read back.");
        } catch (Throwable $exception) {
            return self::reason($exception);
        }
    }

    /** What went wrong, in a line: the storage's own message (a wrong key, no such bucket), or the root cause. */
    private static function reason(Throwable $exception): string
    {
        $chain = [];
        for ($current = $exception; $current; $current = $current->getPrevious()) {
            $chain[] = $current;
        }
        foreach ($chain as $link) {
            if ($link instanceof AwsException && $link->getAwsErrorMessage()) {
                return Str::limit($link->getAwsErrorMessage(), 300);
            }
        }
        $root = end($chain);
        $message = preg_replace('/\s+/', ' ', trim($root->getMessage()));

        // cURL's reason, without the request it was making
        if (preg_match('/cURL error \d+: ([^(]+)/', $message, $curl)) {
            return Str::limit(trim($curl[1]), 300);
        }

        return Str::limit($message ?: class_basename($root), 300);
    }

    /** Whether the storage can hand out its own signed links (S3, R2), rather than files going through the app. */
    public static function signsUrls(string $driver): bool
    {
        return $driver !== 'local';
    }
}
