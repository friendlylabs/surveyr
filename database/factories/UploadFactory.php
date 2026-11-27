<?php

namespace Database\Factories;

use App\Models\Form;
use App\Models\Submission;
use App\Models\Upload;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Upload>
 */
class UploadFactory extends Factory
{
    /**
     * Define the model's default state: a small PNG kept on the local disk (write its file with `stored()`).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_id' => Form::factory(),
            'workspace_id' => fn (array $attributes) => Form::withoutGlobalScope('workspace')->find($attributes['form_id'])->workspace_id,
            'disk' => 'local',
            'path' => fn (array $attributes) => 'forms/'.Str::random(40),
            'name' => fake()->word().'.png',
            'type' => 'image/png',
            'size' => 4,
        ];
    }

    /** An upload to the given form, i.e. Upload::factory()->to($form). */
    public function to(Form $form): static
    {
        return $this->state(fn (array $attributes) => ['form_id' => $form->id, 'workspace_id' => $form->workspace_id]);
    }

    /** Sent with the given response. */
    public function sentWith(Submission $submission): static
    {
        return $this->state(fn (array $attributes) => ['form_id' => $submission->form_id, 'workspace_id' => $submission->workspace_id])
            ->afterCreating(function (Upload $upload) use ($submission) {
                $upload->submission()->associate($submission)->save();
            });
    }

    /** With its file written to its storage, holding the given bytes. */
    public function stored(string $bytes = 'PNG1'): static
    {
        return $this->state(['size' => strlen($bytes)])->afterCreating(fn (Upload $upload) => $upload->storage()->put($upload->path, $bytes));
    }
}
