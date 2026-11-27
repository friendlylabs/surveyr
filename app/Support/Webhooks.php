<?php

namespace App\Support;

use App\Exceptions\WebhookRefused;
use App\Jobs\SendWebhook;
use App\Models\Form;
use App\Models\Submission;
use App\Models\Upload;
use App\Models\WebhookDelivery;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Webhooks
 *
 * A form's webhook: each new response is posted to its URL as JSON (queued, SendWebhook, retried), and a test can be
 * sent from its settings. Each request is signed with the form's secret: X-Surveyr-Signature is "sha256=" and the hex
 * HMAC-SHA256 of "{X-Surveyr-Timestamp}.{body}". Files in the answers become signed links that work for
 * FILE_LINK_DAYS days. Requests only go to public addresses, checked when sent and pinned so the name can't be
 * pointed elsewhere in between, and redirects aren't followed (surveyr.webhooks.allow_private lifts the check, for
 * development). Every call is logged as a WebhookDelivery.
 */
class Webhooks
{
    /** Seconds to wait for the receiver to answer. */
    const TIMEOUT = 10;

    /** Seconds to wait for a connection. */
    const CONNECT_TIMEOUT = 5;

    /** How long the links to a response's files work, in days. */
    const FILE_LINK_DAYS = 7;

    /** @var (Closure(string): list<string>)|null looks a host's addresses up in tests */
    private static ?Closure $resolver = null;

    /** A new signing secret. */
    public static function secret(): string
    {
        return 'whsec_'.Str::random(40);
    }

    /** Queues the call telling the form's webhook about a new response, when it has one. */
    public static function queue(Form $form, Submission $submission): ?WebhookDelivery
    {
        if (! $form->webhook_url) {
            return null;
        }

        $delivery = self::delivery($form, WebhookDelivery::RESPONSE_CREATED, $submission);
        SendWebhook::dispatch($delivery)->afterCommit();

        return $delivery;
    }

    /** Sends a test to the form's webhook now, once; the delivery says how it went. */
    public static function test(Form $form): WebhookDelivery
    {
        $delivery = self::delivery($form, WebhookDelivery::TEST);
        self::attempt($delivery, true);

        return $delivery;
    }

    /** Queues a failed response call again, as a new delivery to the form's current webhook. */
    public static function resend(WebhookDelivery $failed): WebhookDelivery
    {
        $form = Form::withoutGlobalScopes()->findOrFail($failed->form_id);
        $delivery = self::delivery($form, $failed->event, Submission::withoutGlobalScopes()->findOrFail($failed->submission_id));
        SendWebhook::dispatch($delivery)->afterCommit();

        return $delivery;
    }

    /** A delivery to the form's webhook, not yet tried. */
    private static function delivery(Form $form, string $event, ?Submission $submission = null): WebhookDelivery
    {
        $delivery = new WebhookDelivery(['event' => $event, 'url' => $form->webhook_url, 'status' => WebhookDelivery::PENDING]);
        $delivery->workspace_id = $form->workspace_id;
        $delivery->form()->associate($form);
        $delivery->submission()->associate($submission);
        $delivery->save();

        return $delivery;
    }

    /**
     * Makes one attempt and records it: delivered on a 2xx answer; otherwise retrying, or failed when this was the
     * last try or it can never work (the webhook or response is gone, the address is refused).
     */
    public static function attempt(WebhookDelivery $delivery, bool $lastTry): void
    {
        $delivery->attempts++;
        $delivery->fill(['response_code' => null, 'response_body' => null, 'error' => null, 'duration_ms' => null]);

        $form = Form::withoutGlobalScopes()->find($delivery->form_id);
        $submission = $delivery->submission_id ? Submission::withoutGlobalScopes()->with('collector', 'uploads')->find($delivery->submission_id) : null;
        $gone = match (true) {
            ! $form || ! $form->webhook_url => __('The form no longer has a webhook.'),
            $delivery->event === WebhookDelivery::RESPONSE_CREATED && ! $submission => __('The response was deleted.'),
            default => null,
        };
        if ($gone) {
            self::finish($delivery, WebhookDelivery::FAILED, $gone);

            return;
        }

        if (! $form->webhook_secret) {
            $form->webhook_secret = self::secret();
            $form->saveQuietly();
        }

        $body = json_encode(self::payload($delivery, $form, $submission), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $timestamp = (string) now()->timestamp;
        $started = hrtime(true);

        try {
            [$host, $port, $address] = self::target($delivery->url);
            $request = Http::withHeaders([
                'User-Agent' => config('app.name').'-Webhooks/1.0',
                'X-Surveyr-Event' => $delivery->event,
                'X-Surveyr-Delivery' => $delivery->hash,
                'X-Surveyr-Timestamp' => $timestamp,
                'X-Surveyr-Signature' => 'sha256='.hash_hmac('sha256', $timestamp.'.'.$body, $form->webhook_secret),
            ])->withBody($body, 'application/json')->timeout(self::TIMEOUT)->connectTimeout(self::CONNECT_TIMEOUT)->withoutRedirecting();
            if ($address !== $host) {
                // connect to the address that was checked, not whatever the name points to by then
                $request->withOptions(['curl' => [CURLOPT_RESOLVE => [$host.':'.$port.':'.(str_contains($address, ':') ? '['.$address.']' : $address)]]]);
            }
            $response = $request->post($delivery->url);
        } catch (WebhookRefused $refused) {
            self::finish($delivery, WebhookDelivery::FAILED, $refused->getMessage());

            return;
        } catch (ConnectionException $failed) {
            $delivery->duration_ms = self::since($started);
            self::finish($delivery, $lastTry ? WebhookDelivery::FAILED : WebhookDelivery::RETRYING, self::reason($failed->getMessage()));

            return;
        }

        $delivery->duration_ms = self::since($started);
        $delivery->response_code = $response->status();
        $delivery->response_body = Str::limit($response->body(), 990) ?: null;
        if ($response->successful()) {
            $delivery->delivered_at = now();
            self::finish($delivery, WebhookDelivery::DELIVERED);
        } else {
            self::finish($delivery, $lastTry ? WebhookDelivery::FAILED : WebhookDelivery::RETRYING, __('The receiver answered HTTP :code.', ['code' => $response->status()]));
        }
    }

    /**
     * What's posted: the event, the form and, for a response, its answers (files as signed links).
     *
     * @return array{event: string, delivery: string, sent_at: string, form: array<string, string>, response: array<string, mixed>|null}
     */
    public static function payload(WebhookDelivery $delivery, Form $form, ?Submission $submission): array
    {
        return [
            'event' => $delivery->event,
            'delivery' => $delivery->hash,
            'sent_at' => now()->toIso8601String(),
            'form' => ['hash' => $form->hash, 'title' => $form->title, 'url' => route('public.forms.show', $form)],
            'response' => $submission ? [
                'hash' => $submission->hash,
                'reference' => $submission->reference,
                'number' => $submission->number,
                'submitted_at' => $submission->created_at->toIso8601String(),
                'collected_by' => $submission->collector ? ['hash' => $submission->collector->hash, 'name' => $submission->collector->name] : null,
                'meta' => (object) ($submission->meta ?? []),
                'data' => (object) self::linkFiles($submission->data ?? [], $submission),
            ] : null,
        ];
    }

    /**
     * The answers with each "upload:{hash}" of the response's own files swapped for a signed link to it.
     *
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    private static function linkFiles(array $data, Submission $submission): array
    {
        $uploads = $submission->uploads->keyBy('hash');
        array_walk_recursive($data, function (mixed &$value) use ($uploads) {
            $hash = Upload::hashFrom($value);
            if ($hash && $uploads->has($hash)) {
                $value = URL::temporarySignedRoute('files.upload', now()->addDays(self::FILE_LINK_DAYS), ['upload' => $hash]);
            }
        });

        return $data;
    }

    /**
     * Where a webhook URL may be sent: its host, port and the public address to connect to.
     *
     * @return array{0: string, 1: int, 2: string}
     *
     * @throws WebhookRefused when it's not http(s), can't be found, or points into a private or local network
     */
    public static function target(string $url): array
    {
        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        if (! in_array($scheme, ['http', 'https'], true) || empty($parts['host'])) {
            throw new WebhookRefused(__('The webhook URL must start with http:// or https://.'));
        }

        $host = trim($parts['host'], '[]');
        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));
        $addresses = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : self::resolve($host);
        if (! $addresses) {
            throw new WebhookRefused(__('Couldn\'t find :host.', ['host' => $host]));
        }

        if (! config('surveyr.webhooks.allow_private')) {
            foreach ($addresses as $address) {
                if (! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE)) {
                    throw new WebhookRefused(__('Webhooks can\'t be sent to private or local addresses (:host).', ['host' => $host]));
                }
            }
        }

        return [$host, $port, $addresses[0]];
    }

    /**
     * A host's addresses, IPv4 first.
     *
     * @return list<string>
     */
    private static function resolve(string $host): array
    {
        if (self::$resolver) {
            return (self::$resolver)($host);
        }

        $v4 = gethostbynamel($host) ?: [];
        $v6 = array_column(@dns_get_record($host, DNS_AAAA) ?: [], 'ipv6');

        return array_values(array_merge($v4, $v6));
    }

    /**
     * Looks hosts up with the given closure instead of DNS (tests); null goes back to DNS.
     *
     * @param  (Closure(string): list<string>)|null  $resolver
     */
    public static function resolveUsing(?Closure $resolver): void
    {
        self::$resolver = $resolver;
    }

    /** Saves the attempt's outcome. */
    private static function finish(WebhookDelivery $delivery, string $status, ?string $error = null): void
    {
        $delivery->status = $status;
        $delivery->error = $error ? Str::limit($error, 490) : null;
        $delivery->save();
    }

    /** Milliseconds since an hrtime() reading. */
    private static function since(int $started): int
    {
        return (int) round((hrtime(true) - $started) / 1_000_000);
    }

    /** A connection failure in a few words: cURL's reason without its error number and link. */
    private static function reason(string $message): string
    {
        $message = preg_replace('~^cURL error \d+:\s*~', '', $message);
        $message = preg_replace('~\s*\(see https?://\S+\).*$~s', '', $message);

        return trim($message) ?: __('Couldn\'t connect.');
    }
}
