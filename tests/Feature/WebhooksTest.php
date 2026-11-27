<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\Form;
use App\Models\Submission;
use App\Models\Upload;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\Workspace;
use App\Support\Webhooks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * WebhooksTest
 *
 * A form's webhook (App\Support\Webhooks, SendWebhook, FormWebhooksController): each new response is posted to it as
 * signed JSON in the background and retried when it fails; requests never go to private or local addresses; a test can
 * be sent from the settings, the secret replaced and a failed call resent; every call is logged on the form.
 */
class WebhooksTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://hooks.example.org/surveyr';

    private Workspace $workspace;

    private User $owner;

    private Form $form;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
        Webhooks::resolveUsing(fn (string $host) => ['93.184.215.14']);
        $this->workspace = Workspace::factory()->create();
        $this->owner = User::factory()->create();
        $this->workspace->addMember($this->owner, WorkspaceRole::Member);
        $this->owner->switchWorkspace($this->workspace);
        $this->form = Form::factory()->in($this->workspace)->create([
            'user_id' => $this->owner->id, 'title' => 'Water access', 'webhook_url' => self::URL, 'webhook_secret' => 'whsec_test',
            'content' => ['pages' => [['name' => 'page1', 'elements' => [['type' => 'text', 'name' => 'village'], ['type' => 'file', 'name' => 'photo']]]]],
        ]);
    }

    protected function tearDown(): void
    {
        Webhooks::resolveUsing(null);
        parent::tearDown();
    }

    private function respond(array $data = ['village' => 'Kongwa']): void
    {
        $this->postJson(route('public.forms.submit', $this->form), ['data' => $data])->assertOk();
    }

    public function test_a_new_response_is_posted_signed_to_the_webhook(): void
    {
        Http::fake([self::URL => Http::response('ok')]);

        $this->respond();

        $submission = Submission::withoutGlobalScopes()->sole();
        $delivery = WebhookDelivery::withoutGlobalScopes()->sole();
        $this->assertSame([WebhookDelivery::RESPONSE_CREATED, WebhookDelivery::DELIVERED, 1, 200, $submission->id], [$delivery->event, $delivery->status, $delivery->attempts, $delivery->response_code, $delivery->submission_id]);
        $this->assertNotNull($delivery->delivered_at);

        Http::assertSent(function (Request $request) use ($submission, $delivery) {
            $signature = 'sha256='.hash_hmac('sha256', $request->header('X-Surveyr-Timestamp')[0].'.'.$request->body(), 'whsec_test');

            return $request->url() === self::URL && $request->method() === 'POST'
                && $request->hasHeader('X-Surveyr-Signature', $signature)
                && $request->hasHeader('X-Surveyr-Event', 'response.created')
                && $request->hasHeader('X-Surveyr-Delivery', $delivery->hash)
                && $request['event'] === 'response.created'
                && $request['form']['hash'] === $this->form->hash
                && $request['response']['hash'] === $submission->hash
                && $request['response']['reference'] === 'RSP-1'
                && $request['response']['data'] === ['village' => 'Kongwa'];
        });
    }

    public function test_the_responses_own_files_are_sent_as_signed_links(): void
    {
        Http::fake([self::URL => Http::response('ok')]);
        $submission = Submission::factory()->to($this->form)->create();
        $upload = Upload::factory()->sentWith($submission)->create();
        $submission->update(['data' => ['photo' => [['name' => 'well.png', 'content' => $upload->reference()]], 'note' => 'upload:'.str_repeat('a', 20)]]);

        $payload = Webhooks::payload(WebhookDelivery::factory()->about($submission)->create(), $this->form, $submission->fresh(['uploads']));

        $link = $payload['response']['data']->photo[0]['content'];
        $this->assertStringContainsString('/files/uploads/'.$upload->hash, $link);
        $this->assertStringContainsString('signature=', $link);
        // a reference to a file that isn't this response's is left as it is
        $this->assertSame('upload:'.str_repeat('a', 20), $payload['response']['data']->note);
    }

    public function test_no_webhook_means_nothing_is_sent(): void
    {
        $this->form->update(['webhook_url' => null]);
        Http::fake();

        $this->respond();

        Http::assertNothingSent();
        $this->assertSame(0, WebhookDelivery::withoutGlobalScopes()->count());
    }

    public function test_a_failed_call_is_retried_then_marked_failed(): void
    {
        Http::fake([self::URL => Http::response('down', 503)]);

        $this->respond();

        $delivery = WebhookDelivery::withoutGlobalScopes()->sole();
        $this->assertSame([WebhookDelivery::RETRYING, 1, 503, 'down'], [$delivery->status, $delivery->attempts, $delivery->response_code, $delivery->response_body]);
        $this->assertSame(__('The receiver answered HTTP :code.', ['code' => 503]), $delivery->error);

        Webhooks::attempt($delivery, true);
        $this->assertSame([WebhookDelivery::FAILED, 2], [$delivery->fresh()->status, $delivery->fresh()->attempts]);
    }

    public function test_a_connection_failure_is_recorded_in_plain_words(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out after 10001 milliseconds (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) for https://hooks.example.org/surveyr'));

        $this->respond();

        $this->assertSame('Operation timed out after 10001 milliseconds', WebhookDelivery::withoutGlobalScopes()->sole()->error);
    }

    public function test_private_and_local_addresses_are_refused_without_retrying(): void
    {
        Http::fake();

        foreach (['http://127.0.0.1:8080/hook', 'http://[::1]/hook', 'http://169.254.169.254/latest/meta-data', 'https://intranet.example.org/hook'] as $url) {
            Webhooks::resolveUsing(fn (string $host) => ['10.0.0.5']);
            $this->form->update(['webhook_url' => $url]);
            $delivery = Webhooks::queue($this->form, Submission::factory()->to($this->form)->create())->fresh();

            $this->assertSame(WebhookDelivery::FAILED, $delivery->status, $url);
            $this->assertStringContainsString('private or local addresses', $delivery->error);
        }
        Http::assertNothingSent();
    }

    public function test_private_addresses_can_be_allowed_for_development(): void
    {
        config(['surveyr.webhooks.allow_private' => true]);
        $this->form->update(['webhook_url' => 'http://127.0.0.1:8080/hook']);
        Http::fake(['127.0.0.1:8080/*' => Http::response('ok')]);

        $this->respond();

        $this->assertSame(WebhookDelivery::DELIVERED, WebhookDelivery::withoutGlobalScopes()->sole()->status);
    }

    public function test_a_retry_after_the_webhook_was_removed_stops(): void
    {
        Http::fake([self::URL => Http::response('down', 500)]);
        $this->respond();
        $this->form->update(['webhook_url' => null]);

        $delivery = WebhookDelivery::withoutGlobalScopes()->sole();
        Webhooks::attempt($delivery, false);

        $this->assertSame([WebhookDelivery::FAILED, __('The form no longer has a webhook.')], [$delivery->status, $delivery->error]);
        Http::assertSentCount(1);
    }

    public function test_send_test_posts_now_and_says_how_it_went(): void
    {
        Http::fakeSequence(self::URL)->push('ok', 200)->push('nope', 404);

        $this->actingAs($this->owner)->postJson(route('forms.webhook.test', $this->form))
            ->assertOk()->assertJsonPath('message', __('Test delivered (HTTP :code).', ['code' => 200]));
        $this->postJson(route('forms.webhook.test', $this->form))
            ->assertStatus(422)->assertJsonPath('message', __('The test wasn\'t delivered: :error', ['error' => __('The receiver answered HTTP :code.', ['code' => 404])]));

        $this->assertSame([WebhookDelivery::FAILED, WebhookDelivery::DELIVERED], WebhookDelivery::withoutGlobalScopes()->latest('id')->pluck('status')->all());
        Http::assertSent(fn (Request $request) => $request['event'] === 'test' && $request['response'] === null);
    }

    public function test_send_test_needs_a_saved_webhook_and_an_editor(): void
    {
        $this->form->update(['webhook_url' => null]);
        $this->actingAs($this->owner)->postJson(route('forms.webhook.test', $this->form))->assertStatus(422);

        $member = User::factory()->create();
        $this->workspace->addMember($member, WorkspaceRole::Member);
        $member->switchWorkspace($this->workspace);
        $this->actingAs($member)->postJson(route('forms.webhook.test', $this->form))->assertForbidden();
        $this->postJson(route('forms.webhook.secret', $this->form))->assertForbidden();
    }

    public function test_a_webhook_gets_a_secret_when_first_saved_and_a_new_one_on_request(): void
    {
        $form = Form::factory()->in($this->workspace)->create(['user_id' => $this->owner->id]);

        $this->actingAs($this->owner)->postJson(route('forms.settings.submission', $form), ['webhook_url' => self::URL])->assertOk();
        $secret = $form->fresh()->webhook_secret;
        $this->assertStringStartsWith('whsec_', $secret);

        // kept when the URL changes, replaced on request; never in the form's JSON
        $this->postJson(route('forms.settings.submission', $form), ['webhook_url' => 'https://hooks.example.org/other'])->assertOk();
        $this->assertSame($secret, $form->fresh()->webhook_secret);
        $this->postJson(route('forms.webhook.secret', $form))->assertOk();
        $this->assertNotSame($secret, $form->fresh()->webhook_secret);
        $this->assertArrayNotHasKey('webhook_secret', $form->fresh()->toArray());
    }

    public function test_a_failed_response_call_can_be_resent(): void
    {
        Http::fake([self::URL => Http::response('ok')]);
        $failed = WebhookDelivery::factory()->about(Submission::factory()->to($this->form)->create())->failed()->create();
        $delivered = WebhookDelivery::factory()->about(Submission::factory()->to($this->form)->create())->create();

        $this->actingAs($this->owner)->postJson(route('forms.webhook.resend', [$this->form, $failed]))->assertOk();
        $this->assertSame(WebhookDelivery::DELIVERED, WebhookDelivery::withoutGlobalScopes()->latest('id')->first()->status);
        $this->assertSame(WebhookDelivery::FAILED, $failed->fresh()->status);

        $this->postJson(route('forms.webhook.resend', [$this->form, $delivered]))->assertStatus(422);
        $other = Form::factory()->in($this->workspace)->create(['user_id' => $this->owner->id, 'webhook_url' => self::URL]);
        $this->postJson(route('forms.webhook.resend', [$other, $failed]))->assertNotFound();
    }

    public function test_the_settings_put_each_webhook_action_beside_its_field(): void
    {
        WebhookDelivery::factory()->failed()->create(['form_id' => $this->form->id, 'error' => 'The receiver answered HTTP 500.']);

        $this->actingAs($this->owner)->get(route('forms.show', [$this->form, 'tab' => 'settings']))->assertOk()
            ->assertSeeInOrder(['id="formWebhookUrl"', 'form="webhookTestForm"', 'id="formWebhookSecret"', 'form="webhookSecretForm"', __('Save changes')], false)
            ->assertSee(route('forms.webhook.test', $this->form), false)
            // the calls are logged but not listed for now
            ->assertDontSee('The receiver answered HTTP 500.');
    }

    public function test_old_calls_are_pruned(): void
    {
        $old = WebhookDelivery::factory()->create(['form_id' => $this->form->id, 'created_at' => now()->subDays(WebhookDelivery::KEEP_DAYS + 1)]);
        $recent = WebhookDelivery::factory()->create(['form_id' => $this->form->id]);

        $this->artisan('model:prune', ['--model' => [WebhookDelivery::class]])->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($recent);
    }
}
