<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\FileStorage;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * StorageSettingsTest
 *
 * Admin › Settings › Storage (Admin\SettingsController): admins pick where uploads are kept (this server, Amazon S3 or
 * Cloudflare R2) and enter its details, which are checked before they're saved, kept encrypted, and whose secret is
 * never shown back (left empty, it's kept).
 */
class StorageSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = User::factory()->admin()->create();
    }

    private function s3(array $overrides = []): array
    {
        return $overrides + ['driver' => 's3', 's3_key' => 'AKIAEXAMPLE', 's3_secret' => 'top-secret', 's3_bucket' => 'surveys', 's3_region' => 'eu-west-1'];
    }

    public function test_only_admins_reach_the_settings(): void
    {
        $member = User::factory()->create();
        $this->actingAs($member)->get(route('admin.settings'))->assertForbidden();
        $this->postJson(route('admin.settings.storage'), ['driver' => 'local'])->assertForbidden();

        $this->actingAs($this->admin)->get(route('admin.settings'))->assertOk()->assertSee(__('Storage'))->assertSee(__('Cloudflare R2'));
    }

    public function test_a_storage_that_works_is_saved_and_new_uploads_go_there(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.settings.storage'), $this->s3())->assertOk()->assertJsonPath('success', true);

        Settings::forget();
        $this->assertSame('s3', FileStorage::active());
        $this->assertSame(['key' => 'AKIAEXAMPLE', 'secret' => 'top-secret', 'region' => 'eu-west-1', 'bucket' => 'surveys', 'endpoint' => null, 'path_style' => false], Settings::get('storage.s3'));
        $config = FileStorage::config('s3');
        $this->assertSame(['s3', 'surveys', 'eu-west-1'], [$config['driver'], $config['bucket'], $config['region']]);

        // kept encrypted
        $this->assertStringNotContainsString('top-secret', Setting::where('key', 'storage.s3')->first()->getRawOriginal('value'));
    }

    public function test_r2_points_at_the_accounts_endpoint(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.settings.storage'), [
            'driver' => 'r2', 'r2_account' => 'abc123', 'r2_key' => 'key', 'r2_secret' => 'secret', 'r2_bucket' => 'surveys',
        ])->assertOk();

        Settings::forget();
        $this->assertSame('r2', FileStorage::active());
        $config = FileStorage::config('r2');
        $this->assertSame(['https://abc123.r2.cloudflarestorage.com', 'auto', 'surveys'], [$config['endpoint'], $config['region'], $config['bucket']]);
    }

    public function test_a_storage_that_doesnt_work_is_not_saved(): void
    {
        FileStorage::fake(fn () => 'The specified bucket does not exist');

        $this->actingAs($this->admin)->postJson(route('admin.settings.storage'), $this->s3())
            ->assertStatus(422)->assertJsonPath('message', __('Couldn\'t use this storage: :error', ['error' => 'The specified bucket does not exist']));

        Settings::forget();
        $this->assertSame('local', FileStorage::active());
        $this->assertNull(Settings::get('storage.s3'));
    }

    public function test_test_connection_checks_without_saving(): void
    {
        $tried = null;
        FileStorage::fake(function (string $driver, array $details) use (&$tried) {
            $tried = [$driver, $details['bucket']];

            return null;
        });

        $this->actingAs($this->admin)->postJson(route('admin.settings.storage.test'), $this->s3())->assertOk()->assertJsonPath('success', true);

        $this->assertSame(['s3', 'surveys'], $tried);
        Settings::forget();
        $this->assertSame('local', FileStorage::active());
    }

    public function test_the_details_each_storage_needs_are_required(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.settings.storage'), ['driver' => 's3'])
            ->assertStatus(422)->assertJsonValidationErrors(['s3_key', 's3_secret', 's3_bucket']);
        $this->postJson(route('admin.settings.storage'), ['driver' => 'r2', 'r2_account' => 'not/valid'])
            ->assertStatus(422)->assertJsonValidationErrors(['r2_account', 'r2_key', 'r2_secret', 'r2_bucket']);
        $this->postJson(route('admin.settings.storage'), ['driver' => 'dropbox'])->assertStatus(422)->assertJsonValidationErrors('driver');
    }

    public function test_the_saved_secret_is_never_shown_and_is_kept_when_left_empty(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.settings.storage'), $this->s3())->assertOk();

        $this->get(route('admin.settings'))->assertOk()->assertDontSee('top-secret')->assertSee('AKIAEXAMPLE')->assertSee(__('Saved. Leave empty to keep it.'));

        $this->postJson(route('admin.settings.storage'), $this->s3(['s3_secret' => '', 's3_bucket' => 'renamed']))->assertOk();
        Settings::forget();
        $this->assertSame(['top-secret', 'renamed'], [Settings::get('storage.s3')['secret'], Settings::get('storage.s3')['bucket']]);
    }

    public function test_switching_back_to_this_server_keeps_the_other_storages_details(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.settings.storage'), $this->s3())->assertOk();
        $this->postJson(route('admin.settings.storage'), ['driver' => 'local'])->assertOk();

        Settings::forget();
        $this->assertSame('local', FileStorage::active());
        $this->assertSame('surveys', Settings::get('storage.s3')['bucket']);
    }
}
