<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use App\Models\Concerns\HasHash;
use Database\Factories\WebhookDeliveryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WebhookDelivery
 *
 * One call of a form's webhook (App\Support\Webhooks): a new response (response.created) or a test, where it went
 * and how its last attempt went. A response's call is queued and tried up to SendWebhook::TRIES times; a test is sent
 * at once, once. Kept KEEP_DAYS days (model:prune, routes/console.php), shown on the form's settings.
 */
#[Fillable(['event', 'url', 'status', 'attempts', 'response_code', 'response_body', 'error', 'duration_ms', 'delivered_at'])]
#[Hidden(['id', 'workspace_id', 'form_id', 'submission_id'])]
class WebhookDelivery extends Model
{
    /** @use HasFactory<WebhookDeliveryFactory> */
    use BelongsToWorkspace, HasFactory, HasHash, Prunable;

    /** A new response was sent. */
    const RESPONSE_CREATED = 'response.created';

    /** Sent from the settings to try the webhook. */
    const TEST = 'test';

    /** Waiting for its first attempt. */
    const PENDING = 'pending';

    /** An attempt failed; it'll be tried again. */
    const RETRYING = 'retrying';

    /** The receiver answered 2xx. */
    const DELIVERED = 'delivered';

    /** Every attempt failed, or it can't be sent. */
    const FAILED = 'failed';

    /** How long deliveries are kept, in days. */
    const KEEP_DAYS = 30;

    /** Column types and casting. */
    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'response_code' => 'integer',
            'duration_ms' => 'integer',
            'delivered_at' => 'datetime',
        ];
    }

    /** Deliveries older than KEEP_DAYS days. */
    public function prunable(): Builder
    {
        return static::withoutGlobalScopes()->where('created_at', '<', now()->subDays(self::KEEP_DAYS));
    }

    /** Whether it's finished (delivered or failed). */
    public function isDone(): bool
    {
        return in_array($this->status, [self::DELIVERED, self::FAILED], true);
    }

    /** Whether it may be sent again: a response's call that failed, while the response is still there. */
    public function canResend(): bool
    {
        return $this->status === self::FAILED && $this->event === self::RESPONSE_CREATED && $this->submission_id !== null;
    }

    /** The form whose webhook it called. */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /** The response it told about (none for a test). */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
