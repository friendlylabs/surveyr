<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\WebhookDelivery;
use App\Support\Webhooks;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

/**
 * FormWebhooksController
 *
 * The webhook part of a form's Submission settings (App\Support\Webhooks): send a test now, give it a new signing
 * secret, and send a failed response's call again. The URL itself is saved with the rest of the section
 * (FormSettingsController@updateSubmission). Editors only (FormPolicy::update).
 */
class FormWebhooksController extends Controller
{
    /** Sends a test to the saved webhook at once and says how it went (the page then reloads to show it in the log). */
    public function test(Form $form): JsonResponse
    {
        Gate::authorize('update', $form);

        if (! $form->webhook_url) {
            return $this->jsonError(__('Save a webhook URL first.'), 422);
        }

        $delivery = Webhooks::test($form);

        if ($delivery->status !== WebhookDelivery::DELIVERED) {
            return $this->jsonError(__('The test wasn\'t delivered: :error', ['error' => $delivery->error]), 422);
        }

        return $this->jsonSuccess(__('Test delivered (HTTP :code).', ['code' => $delivery->response_code]));
    }

    /** Replaces the signing secret; the receiver has to be given the new one. */
    public function secret(Form $form): JsonResponse
    {
        Gate::authorize('update', $form);

        $form->webhook_secret = Webhooks::secret();
        $form->save();
        $this->redirect = route('forms.show', [$form, 'tab' => 'settings']);

        return $this->jsonSuccess(__('New secret made. Update it where you check the signature.'));
    }

    /** Queues a failed response call again. */
    public function resend(Form $form, WebhookDelivery $delivery): JsonResponse
    {
        Gate::authorize('update', $form);
        abort_unless($delivery->form_id === $form->id, 404);

        if (! $delivery->canResend()) {
            return $this->jsonError(__('Only a response\'s failed call can be sent again.'), 422);
        }
        if (! $form->webhook_url) {
            return $this->jsonError(__('Save a webhook URL first.'), 422);
        }

        Webhooks::resend($delivery);
        $this->redirect = route('forms.show', [$form, 'tab' => 'settings']);

        return $this->jsonSuccess(__('Sending it again.'));
    }

    /** Registers the webhook routes (inside the forms prefix). */
    public static function routes(): void
    {
        Route::post('/{form}/webhook/test', [self::class, 'test'])->middleware('throttle:10,1')->name('forms.webhook.test');
        Route::post('/{form}/webhook/secret', [self::class, 'secret'])->name('forms.webhook.secret');
        Route::post('/{form}/webhook/{delivery}/resend', [self::class, 'resend'])->name('forms.webhook.resend');
    }
}
