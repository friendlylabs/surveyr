<?php

namespace Tests\Feature;

use App\Enums\SpaceRole;
use App\Enums\WorkspaceRole;
use App\Models\Form;
use App\Models\Space;
use App\Models\User;
use App\Models\Workspace;
use App\Support\FormTheme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * FormBuilderTest
 *
 * The builder and its autosave (FormBuilderController): who may edit or preview, saving the definition, the form's
 * title following the survey's, and one editor not overwriting another's changes.
 */
class FormBuilderTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->workspace = Workspace::factory()->create();
    }

    /** A workspace member with the given workspace role. */
    private function member(WorkspaceRole $role = WorkspaceRole::Member): User
    {
        $user = User::factory()->create();
        $this->workspace->addMember($user, $role);
        $user->switchWorkspace($this->workspace);

        return $user;
    }

    /** A form in a space where $user has $role. */
    private function formInSpace(User $user, SpaceRole $role): Form
    {
        $space = Space::factory()->in($this->workspace)->withMember($user, $role)->create();
        $form = Form::factory()->in($this->workspace)->create(['title' => 'Water access']);
        $form->spaces()->attach($space);

        return $form;
    }

    public function test_editors_open_the_builder_and_viewers_only_the_preview(): void
    {
        $editor = $this->member();
        $form = $this->formInSpace($editor, SpaceRole::Editor);
        $viewer = $this->member();
        $form->spaces->first()->addMember($viewer, SpaceRole::Viewer);

        $this->actingAs($editor)->get(route('forms.build', $form))->assertOk()
            ->assertSee('formBuilderConfig', false)
            ->assertSee(json_encode(route('forms.build.save', $form)), false);

        $this->actingAs($viewer)->get(route('forms.build', $form))->assertForbidden();
        $this->actingAs($viewer)->get(route('forms.preview', $form))->assertOk()
            ->assertSee('formPreviewConfig', false)
            ->assertDontSee(route('forms.build', $form), false);

        $this->actingAs($this->member())->get(route('forms.preview', $form))->assertForbidden();
    }

    public function test_autosave_stores_the_definition_and_takes_the_surveys_title(): void
    {
        $user = $this->member();
        $form = Form::factory()->in($this->workspace)->ownedBy($user)->create(['title' => 'Old title']);
        $content = ['title' => 'Household water access', 'pages' => [['name' => 'page1', 'elements' => [['type' => 'text', 'name' => 'village']]]]];

        $this->actingAs($user)->postJson(route('forms.build.save', $form), ['content' => $content, 'revision' => 0])
            ->assertOk()->assertJson(['success' => true, 'revision' => 1, 'title' => 'Household water access']);

        $form->refresh();
        $this->assertSame($content, $form->content);
        $this->assertSame(1, $form->revision);
        $this->assertSame('Household water access', $form->title);
        $this->assertSame('household-water-access', $form->slug);
    }

    public function test_a_translated_survey_title_uses_the_default_language(): void
    {
        $user = $this->member();
        $form = Form::factory()->in($this->workspace)->ownedBy($user)->create();

        $this->actingAs($user)->postJson(route('forms.build.save', $form), [
            'content' => ['title' => ['default' => 'Water survey', 'fr' => 'Enquête eau'], 'pages' => []],
            'revision' => 0,
        ])->assertOk();

        $this->assertSame('Water survey', $form->fresh()->title);
    }

    public function test_a_save_from_a_stale_revision_is_refused(): void
    {
        $user = $this->member();
        $form = Form::factory()->in($this->workspace)->ownedBy($user)->create();

        $this->actingAs($user)->postJson(route('forms.build.save', $form), ['content' => ['title' => 'First', 'pages' => []], 'revision' => 0])->assertOk();

        // a second editor still on revision 0
        $this->postJson(route('forms.build.save', $form), ['content' => ['title' => 'Second', 'pages' => []], 'revision' => 0])
            ->assertStatus(409)->assertJson(['success' => false, 'revision' => 1]);

        $this->assertSame('First', $form->fresh()->title);
    }

    public function test_viewers_cannot_save(): void
    {
        $viewer = $this->member();
        $form = $this->formInSpace($viewer, SpaceRole::Viewer);

        $this->actingAs($viewer)->postJson(route('forms.build.save', $form), ['content' => ['pages' => []], 'revision' => 0])->assertForbidden();
        $this->assertSame(0, $form->fresh()->revision);
    }

    public function test_the_content_must_be_a_json_object(): void
    {
        $user = $this->member();
        $form = Form::factory()->in($this->workspace)->ownedBy($user)->create();

        $this->actingAs($user)->postJson(route('forms.build.save', $form), ['content' => 'not json', 'revision' => 0])
            ->assertStatus(422)->assertJsonValidationErrors('content');
    }

    public function test_form_content_cannot_break_out_of_the_page_config(): void
    {
        $user = $this->member();
        $form = Form::factory()->in($this->workspace)->ownedBy($user)->create([
            'content' => ['title' => '</script><script>alert(1)</script>', 'pages' => []],
        ]);

        $this->actingAs($user)->get(route('forms.build', $form))->assertOk()
            ->assertSee('alert(1)', false)
            ->assertDontSee('<script>alert(1)', false);
    }

    /** A full set of theme choices, with $overrides. */
    private function themeChoices(array $overrides = []): array
    {
        return $overrides + ['style' => 'brutalist', 'layout' => 'panels', 'palette' => 'ocean', 'mode' => 'dark', 'font' => 'serif'];
    }

    public function test_the_theme_is_saved_with_the_form(): void
    {
        $user = $this->member();
        $form = Form::factory()->in($this->workspace)->ownedBy($user)->create();

        $this->actingAs($user)->postJson(route('forms.theme', $form), $this->themeChoices(['color' => '#123456']))->assertOk();

        $this->assertSame(
            ['style' => 'brutalist', 'layout' => 'panels', 'palette' => 'ocean', 'color' => null, 'mode' => 'dark', 'font' => 'serif', 'backgroundImage' => null],
            $form->fresh()->theme,
            'the colour is kept only with the custom palette'
        );
        $this->get(route('forms.preview', $form))->assertSee('brutalist', false);
    }

    public function test_a_custom_palette_needs_a_hex_colour(): void
    {
        $user = $this->member();
        $form = Form::factory()->in($this->workspace)->ownedBy($user)->create();

        $this->actingAs($user)->postJson(route('forms.theme', $form), $this->themeChoices(['palette' => 'custom']))
            ->assertStatus(422)->assertJsonValidationErrors('color');
        $this->postJson(route('forms.theme', $form), $this->themeChoices(['palette' => 'custom', 'color' => 'red']))
            ->assertStatus(422)->assertJsonValidationErrors('color');

        $this->postJson(route('forms.theme', $form), $this->themeChoices(['palette' => 'custom', 'color' => '#E6852F']))->assertOk();
        $this->assertSame('#e6852f', $form->fresh()->theme['color']);
    }

    public function test_unknown_theme_choices_are_refused(): void
    {
        $user = $this->member();
        $form = Form::factory()->in($this->workspace)->ownedBy($user)->create();

        $this->actingAs($user)->postJson(route('forms.theme', $form), $this->themeChoices(['style' => 'neon', 'font' => 'comic']))
            ->assertStatus(422)->assertJsonValidationErrors(['style', 'font']);
    }

    public function test_a_background_image_must_be_one_of_the_forms_uploads(): void
    {
        $user = $this->member();
        $form = Form::factory()->in($this->workspace)->ownedBy($user)->create();

        $this->actingAs($user)->postJson(route('forms.theme', $form), $this->themeChoices(['backgroundImage' => 'https://tracker.example.com/pixel.png']))
            ->assertStatus(422)->assertJsonValidationErrors('backgroundImage');

        $own = FormTheme::assetUrl($form, 'abc.png');
        $this->postJson(route('forms.theme', $form), $this->themeChoices(['backgroundImage' => $own]))->assertOk();
        $this->assertSame($own, $form->fresh()->theme['backgroundImage']);
    }

    public function test_images_are_stored_on_the_public_disk(): void
    {
        Storage::fake('public');
        $user = $this->member();
        $form = Form::factory()->in($this->workspace)->ownedBy($user)->create();

        $response = $this->actingAs($user)->post(route('forms.assets', $form), ['file' => UploadedFile::fake()->image('logo.png')], ['Accept' => 'application/json'])
            ->assertOk()->assertJson(['success' => true]);

        $name = basename($response->json('url'));
        Storage::disk('public')->assertExists(FormTheme::assetDirectory($form).'/'.$name);
        $this->assertStringStartsWith(FormTheme::assetUrl($form, ''), $response->json('url'));
    }

    public function test_svg_and_other_files_are_refused(): void
    {
        Storage::fake('public');
        $user = $this->member();
        $form = Form::factory()->in($this->workspace)->ownedBy($user)->create();

        $this->actingAs($user)->post(route('forms.assets', $form), ['file' => UploadedFile::fake()->create('logo.svg', 4, 'image/svg+xml')], ['Accept' => 'application/json'])
            ->assertStatus(422);
        $this->post(route('forms.assets', $form), ['file' => UploadedFile::fake()->create('notes.pdf', 4, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertStatus(422);
    }

    public function test_viewers_cannot_change_the_theme_or_upload(): void
    {
        Storage::fake('public');
        $viewer = $this->member();
        $form = $this->formInSpace($viewer, SpaceRole::Viewer);

        $this->actingAs($viewer)->postJson(route('forms.theme', $form), $this->themeChoices())->assertForbidden();
        $this->post(route('forms.assets', $form), ['file' => UploadedFile::fake()->image('a.png')], ['Accept' => 'application/json'])->assertForbidden();
    }

    public function test_a_new_form_opens_on_its_overview(): void
    {
        $user = $this->member();

        $response = $this->actingAs($user)->postJson(route('forms.store'), ['title' => 'Fresh form'])->assertOk();

        $form = Form::withoutGlobalScope('workspace')->where('title', 'Fresh form')->firstOrFail();
        $response->assertJson(['redirect' => route('forms.show', $form)]);
    }
}
