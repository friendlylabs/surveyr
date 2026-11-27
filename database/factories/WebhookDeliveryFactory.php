<?php

namespace Database\Factories;

use App\Models\Form;
use App\Models\Submission;
use App\Models\WebhookDelivery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookDelivery>
 */
class WebhookDeliveryFactory extends Factory
{
    /**
     * Define the model's default state: a test call that went through.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_id' => Form::factory(),
            'workspace_id' => fn (array $attributes) => Form::withoutGlobalScope('workspace')->find($attributes['form_id'])->workspace_id,
            'event' => WebhookDelivery::TEST,
            'url' => 'https://hooks.example.org/surveyr',
            'status' => WebhookDelivery::DELIVERED,
            'attempts' => 1,
            'response_code' => 200,
            'duration_ms' => 120,
            'delivered_at' => now(),
        ];
    }

    /** A call about the given response. */
    public function about(Submission $submission): static
    {
        return $this->state(fn (array $attributes) => [
            'form_id' => $submission->form_id, 'workspace_id' => $submission->workspace_id, 'event' => WebhookDelivery::RESPONSE_CREATED,
        ])->afterCreating(function (WebhookDelivery $delivery) use ($submission) {
            $delivery->submission()->associate($submission)->save();
        });
    }

    /** Every attempt failed. */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WebhookDelivery::FAILED, 'attempts' => 5, 'response_code' => 500, 'error' => 'HTTP 500', 'delivered_at' => null,
        ]);
    }
}
