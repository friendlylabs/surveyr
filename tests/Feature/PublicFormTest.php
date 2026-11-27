<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\Submission;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PublicFormTest
 *
 * The form as respondents reach it (Public\FormsController): open, archived, not open yet, closed, behind an access
 * code, and the response stored when it's sent.
 */
class PublicFormTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->workspace = Workspace::factory()->create();
    }

    private function form(array $attributes = []): Form
    {
        return Form::factory()->in($this->workspace)->create($attributes);
    }

    public function test_an_open_form_shows_to_anyone(): void
    {
        $form = $this->form(['title' => 'Water access']);

        $this->get(route('public.forms.show', $form))->assertOk()
            ->assertSee('publicFormConfig', false)
            ->assertSee(json_encode(route('public.forms.submit', $form)), false);
        $this->assertStringContainsString("/form/{$form->hash}", route('public.forms.show', $form));
    }

    public function test_archived_scheduled_and_closed_forms_say_why(): void
    {
        $this->get(route('public.forms.show', $this->form(['archived_at' => now()])))->assertOk()
            ->assertSee(__('This form is no longer available'))->assertDontSee('publicFormConfig', false);
        $this->get(route('public.forms.show', $this->form(['opens_at' => now()->addDay()])))->assertOk()
            ->assertSee(__("This form isn't open yet"));
        $this->get(route('public.forms.show', $this->form(['closes_at' => now()->subDay()])))->assertOk()
            ->assertSee(__('This form is closed'));
    }

    public function test_a_response_is_stored_with_the_forms_workspace(): void
    {
        $form = $this->form();

        $this->postJson(route('public.forms.submit', $form), [
            'data' => ['village' => 'Kongwa', 'source' => 'borehole'],
            'meta' => ['locale' => 'en', 'seconds' => 42],
        ])->assertOk()->assertJson(['success' => true]);

        $submission = Submission::withoutGlobalScope('workspace')->where('form_id', $form->id)->firstOrFail();
        $this->assertSame(['village' => 'Kongwa', 'source' => 'borehole'], $submission->data);
        $this->assertSame(['locale' => 'en', 'seconds' => 42], $submission->meta);
        $this->assertSame($this->workspace->id, $submission->workspace_id);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{20}$/', $submission->hash);
        $this->assertTrue($form->fresh()->hasResponses());
    }

    public function test_a_form_that_isnt_open_takes_no_responses(): void
    {
        foreach ([['closes_at' => now()->subDay()], ['opens_at' => now()->addDay()], ['archived_at' => now()]] as $attributes) {
            $this->postJson(route('public.forms.submit', $this->form($attributes)), ['data' => ['a' => 1]])->assertForbidden();
        }

        $this->assertSame(0, Submission::withoutGlobalScope('workspace')->count());
    }

    public function test_the_access_code_opens_the_form_for_the_session(): void
    {
        $form = $this->form(['access_code' => 'river42']);

        $this->get(route('public.forms.show', $form))->assertOk()
            ->assertSee(__('Access code'))->assertDontSee('publicFormConfig', false);
        $this->postJson(route('public.forms.submit', $form), ['data' => ['a' => 1]])->assertForbidden();

        $this->from(route('public.forms.show', $form))->post(route('public.forms.unlock', $form), ['access_code' => 'wrong'])
            ->assertRedirect(route('public.forms.show', $form))->assertSessionHasErrors('access_code');

        $this->post(route('public.forms.unlock', $form), ['access_code' => 'river42'])->assertRedirect(route('public.forms.show', $form));
        $this->get(route('public.forms.show', $form))->assertSee('publicFormConfig', false);
        $this->postJson(route('public.forms.submit', $form), ['data' => ['a' => 1]])->assertOk();
    }

    public function test_the_answers_must_be_an_object(): void
    {
        $this->postJson(route('public.forms.submit', $this->form()), ['data' => 'nope'])
            ->assertStatus(422)->assertJsonValidationErrors('data');
    }

    public function test_a_form_with_responses_can_only_be_archived(): void
    {
        $owner = User::factory()->create();
        $this->workspace->addMember($owner);
        $owner->switchWorkspace($this->workspace);
        $form = Form::factory()->in($this->workspace)->ownedBy($owner)->create();
        Submission::factory()->to($form)->create();

        $this->actingAs($owner)->postJson(route('forms.destroy', $form))->assertForbidden();
        $this->postJson(route('forms.archive', $form))->assertOk();
        $this->assertModelExists($form);
    }

    public function test_an_unknown_form_is_not_found(): void
    {
        $this->get('/form/doesnotexist0000000')->assertNotFound();
    }
}
