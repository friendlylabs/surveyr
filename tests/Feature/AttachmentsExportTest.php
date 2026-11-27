<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\Form;
use App\Models\Submission;
use App\Models\Upload;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\ResponsesExportFailed;
use App\Notifications\ResponsesExportReady;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

/**
 * AttachmentsExportTest
 *
 * Just the attachments of a form's responses (FormResponsesController@attachments), as the legacy app exported them:
 * zipped in the background (ExportResponses, AttachmentsExport) into RSP-12/question/file folders with a manifest, and
 * emailed as a signed link. File questions, signature pads and questions in dynamic panels all count; links elsewhere
 * are listed, never fetched.
 */
class AttachmentsExportTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

    private User $owner;

    private Form $form;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
        Notification::fake();
        $this->workspace = Workspace::factory()->create();
        $this->owner = User::factory()->create();
        $this->workspace->addMember($this->owner, WorkspaceRole::Member);
        $this->owner->switchWorkspace($this->workspace);
        $this->form = $this->formWith([
            ['type' => 'text', 'name' => 'village'],
            ['type' => 'file', 'name' => 'photo', 'title' => 'Photo'],
            ['type' => 'signaturepad', 'name' => 'sign'],
        ]);
    }

    private function formWith(array $elements): Form
    {
        return Form::factory()->in($this->workspace)->create(['user_id' => $this->owner->id, 'title' => 'Water access', 'content' => ['pages' => [['name' => 'page1', 'elements' => $elements]]]]);
    }

    private function dataUrl(string $type, string $bytes): string
    {
        return "data:{$type};base64,".base64_encode($bytes);
    }

    /**
     * Asks for the attachments, then opens the emailed zip: its entries by name.
     *
     * @return array<string, string>
     */
    private function exported(Form $form, array $input = []): array
    {
        $this->actingAs($this->owner)->postJson(route('forms.responses.attachments', $form), $input)->assertOk()->assertJsonPath('success', true);

        $url = null;
        Notification::assertSentTo($this->owner, ResponsesExportReady::class, function (ResponsesExportReady $notification) use (&$url) {
            $url = $notification->url;

            return $notification->attachmentsOnly;
        });
        $download = $this->get($url)->assertOk()->assertDownload('water-access-attachments-'.now()->format('Y-m-d').'.zip');

        $path = tempnam(sys_get_temp_dir(), 'zip');
        file_put_contents($path, $download->streamedContent());
        $zip = new ZipArchive;
        $zip->open($path);
        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entries[$zip->getNameIndex($i)] = $zip->getFromIndex($i);
        }
        $zip->close();
        @unlink($path);

        return $entries;
    }

    public function test_the_attachments_are_zipped_by_response_and_question_with_a_manifest(): void
    {
        Submission::factory()->to($this->form)->create(['data' => ['village' => 'Kongwa', 'photo' => [['name' => 'well.png', 'type' => 'image/png', 'content' => $this->dataUrl('image/png', 'PNG1')]]]]);
        Submission::factory()->to($this->form)->create(['data' => ['sign' => $this->dataUrl('image/png', 'SIGN'), 'photo' => [
            ['name' => 'old.jpg', 'content' => 'https://legacy.example/storage/old.jpg'],
        ]]]);
        Submission::factory()->to($this->form)->create(['data' => ['village' => 'No files']]);

        $entries = $this->exported($this->form);

        $this->assertSame('PNG1', $entries['RSP-1/photo/well.png']);
        $this->assertSame('SIGN', $entries['RSP-2/sign/sign.png']);
        $this->assertArrayNotHasKey('RSP-3', array_flip(array_map(fn ($name) => strtok($name, '/'), array_keys($entries))));

        $manifest = $entries['manifest.csv'];
        $this->assertStringContainsString('response,submitted,question,file,status', $manifest);
        $this->assertStringContainsString('RSP-1/photo/well.png,included', $manifest);
        $this->assertStringContainsString('https://legacy.example/storage/old.jpg,external', $manifest);
    }

    public function test_files_kept_in_storage_are_copied_in_and_missing_ones_listed(): void
    {
        $submission = Submission::factory()->to($this->form)->create();
        $kept = Upload::factory()->sentWith($submission)->stored('STORED')->create(['name' => 'well.png']);
        $gone = Upload::factory()->sentWith($submission)->create(['name' => 'gone.png']);
        $submission->update(['data' => ['photo' => [
            ['name' => 'well.png', 'content' => $kept->reference()],
            ['name' => 'gone.png', 'content' => $gone->reference()],
        ]]]);

        $entries = $this->exported($this->form);

        $this->assertSame('STORED', $entries['RSP-1/photo/well.png']);
        $this->assertStringContainsString($gone->reference().',missing', $entries['manifest.csv']);
    }

    public function test_files_in_a_dynamic_panel_get_a_folder_per_entry(): void
    {
        $form = $this->formWith([['type' => 'paneldynamic', 'name' => 'household', 'templateElements' => [['type' => 'file', 'name' => 'photo']]]]);
        Submission::factory()->to($form)->create(['data' => ['household' => [
            ['photo' => [['name' => 'a.png', 'content' => $this->dataUrl('image/png', 'A')]]],
            ['photo' => [['name' => 'b.png', 'content' => $this->dataUrl('image/png', 'B')]]],
        ]]]);

        $entries = $this->exported($form);

        $this->assertSame('A', $entries['RSP-1/household_photo_1/a.png']);
        $this->assertSame('B', $entries['RSP-1/household_photo_2/b.png']);
    }

    public function test_only_the_ticked_responses_attachments_are_exported(): void
    {
        $ticked = Submission::factory()->to($this->form)->create(['data' => ['photo' => [['name' => 'a.png', 'content' => $this->dataUrl('image/png', 'A')]]]]);
        Submission::factory()->to($this->form)->create(['data' => ['photo' => [['name' => 'b.png', 'content' => $this->dataUrl('image/png', 'B')]]]]);

        $entries = $this->exported($this->form, ['responses' => [$ticked->hash]]);

        $this->assertArrayHasKey('RSP-1/photo/a.png', $entries);
        $this->assertArrayNotHasKey('RSP-2/photo/b.png', $entries);
    }

    public function test_it_is_refused_at_once_when_there_is_nothing_to_gather(): void
    {
        Submission::factory()->to($this->form)->create(['data' => ['village' => 'Kongwa']]);
        $this->actingAs($this->owner)->postJson(route('forms.responses.attachments', $this->form))->assertStatus(422)
            ->assertJsonPath('message', __('There are no attachments in these responses.'));

        $plain = $this->formWith([['type' => 'text', 'name' => 'village']]);
        $this->postJson(route('forms.responses.attachments', $plain))->assertStatus(422)->assertJsonPath('message', __('This form has no file questions.'));
        Notification::assertNothingSent();
    }

    public function test_when_none_turn_up_in_the_background_the_email_says_why(): void
    {
        // a dynamic panel's files can't be checked up front, so the job finds out
        $form = $this->formWith([['type' => 'paneldynamic', 'name' => 'household', 'templateElements' => [['type' => 'file', 'name' => 'photo']]]]);
        Submission::factory()->to($form)->create(['data' => ['household' => [['photo' => []]]]]);

        try {
            $this->actingAs($this->owner)->postJson(route('forms.responses.attachments', $form));
        } catch (\Throwable) {
            // the sync queue rethrows the job's failure after reporting it
        }

        Notification::assertSentTo($this->owner, ResponsesExportFailed::class, fn (ResponsesExportFailed $notification) => $notification->reason === __('There are no attachments in these responses.'));
    }

    public function test_people_who_cannot_see_the_form_cannot_export_its_attachments(): void
    {
        $member = User::factory()->create();
        $this->workspace->addMember($member, WorkspaceRole::Member);
        $member->switchWorkspace($this->workspace);

        $this->actingAs($member)->postJson(route('forms.responses.attachments', $this->form))->assertForbidden();
    }

    public function test_the_button_shows_only_for_forms_that_take_files(): void
    {
        Submission::factory()->to($this->form)->create();
        $this->actingAs($this->owner)->get(route('forms.responses', $this->form))->assertSee('data-export-attachments-run', false);

        $signatureOnly = $this->formWith([['type' => 'signaturepad', 'name' => 'sign']]);
        Submission::factory()->to($signatureOnly)->create();
        $this->get(route('forms.responses', $signatureOnly))->assertSee('data-export-attachments-run', false);

        $plain = $this->formWith([['type' => 'text', 'name' => 'village']]);
        Submission::factory()->to($plain)->create();
        $this->get(route('forms.responses', $plain))->assertDontSee('data-export-attachments-run', false)->assertDontSee(__('Export attachments'));
    }
}
