<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\Form;
use App\Models\Submission;
use App\Models\Upload;
use App\Models\User;
use App\Models\Workspace;
use App\Support\FileStorage;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * UploadsTest
 *
 * Files respondents upload, kept privately in the chosen storage (FileStorage; fake disks here): uploaded as they're
 * picked (Public\FormsController@upload), claimed by the response sent from the same browser, seen only through
 * short-lived signed links (Public\FilesController), kept where they went when storage is switched, and deleted with
 * their response, form or (never sent) after a day.
 */
class UploadsTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

    private User $owner;

    private Form $form;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->workspace = Workspace::factory()->create();
        $this->owner = User::factory()->create();
        $this->workspace->addMember($this->owner, WorkspaceRole::Member);
        $this->owner->switchWorkspace($this->workspace);
        $this->form = Form::factory()->in($this->workspace)->create(['user_id' => $this->owner->id, 'content' => ['pages' => [['name' => 'page1', 'elements' => [
            ['type' => 'text', 'name' => 'village'],
            ['type' => 'file', 'name' => 'photo', 'allowMultiple' => true],
        ]]]]]);
    }

    /** Uploads a file to the public form; returns the JSON. */
    private function upload(string $name = 'well.png', string $bytes = 'PNGDATA', ?Form $form = null): array
    {
        $file = UploadedFile::fake()->createWithContent($name, $bytes);

        return $this->postJson(route('public.forms.upload', $form ?? $this->form), ['file' => $file])->assertOk()->assertJsonPath('success', true)->json();
    }

    private function answer(string $content, string $name = 'well.png'): array
    {
        return [['name' => $name, 'type' => 'image/png', 'content' => $content]];
    }

    public function test_a_file_is_kept_privately_in_storage_and_previewed_through_a_signed_link(): void
    {
        $json = $this->upload();

        $upload = Upload::withoutGlobalScopes()->sole();
        $this->assertSame('upload:'.$upload->hash, $json['content']);
        $this->assertSame(['local', 'well.png', 7, null], [$upload->disk, $upload->name, $upload->size, $upload->submission_id]);
        $this->assertSame('PNGDATA', FileStorage::disk('local')->get($upload->path));
        $this->assertStringNotContainsString('well.png', $upload->path);

        $preview = $this->get($json['preview'])->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame('PNGDATA', $preview->streamedContent());

        // the link is signed and doesn't last
        $this->get(route('files.upload', $upload->hash))->assertForbidden();
        $this->travel(2)->hours();
        $this->get($json['preview'])->assertForbidden();
    }

    public function test_uploads_follow_the_forms_state_and_the_size_limit(): void
    {
        $closed = Form::factory()->in($this->workspace)->create(['closes_at' => now()->subDay()]);
        $this->postJson(route('public.forms.upload', $closed), ['file' => UploadedFile::fake()->create('a.pdf', 10)])->assertForbidden();

        $locked = Form::factory()->in($this->workspace)->create(['access_code' => 'river42']);
        $this->postJson(route('public.forms.upload', $locked), ['file' => UploadedFile::fake()->create('a.pdf', 10)])->assertForbidden();

        config(['surveyr.uploads.max_kb' => 5]);
        $this->postJson(route('public.forms.upload', $this->form), ['file' => UploadedFile::fake()->create('big.pdf', 50)])->assertStatus(422)->assertJsonValidationErrors('file');

        $this->assertSame(0, Upload::withoutGlobalScopes()->count());
    }

    public function test_sending_the_response_claims_the_uploads_this_browser_made_and_drops_the_rest(): void
    {
        $mine = $this->upload()['content'];
        $someoneElses = Upload::factory()->to($this->form)->stored()->create();
        $otherForm = Upload::factory()->stored()->create();

        $this->postJson(route('public.forms.submit', $this->form), ['data' => ['village' => 'Kongwa', 'photo' => [
            ...$this->answer($mine), ...$this->answer($someoneElses->reference(), 'b.png'), ...$this->answer($otherForm->reference(), 'c.png'),
        ]]])->assertOk();

        $submission = Submission::withoutGlobalScopes()->sole();
        $this->assertSame($this->answer($mine), $submission->data['photo']);
        $this->assertSame(1, $submission->uploads()->count());
        $this->assertNull($someoneElses->fresh()->submission_id);
        $this->assertNull($otherForm->fresh()->submission_id);

        // claimed once only: sending its reference again takes nothing
        $this->postJson(route('public.forms.submit', $this->form), ['data' => ['photo' => $this->answer($mine)]])->assertOk();
        $this->assertSame([], Submission::withoutGlobalScopes()->latest('id')->first()->data['photo']);
    }

    public function test_the_team_sees_a_sent_file_through_the_app_then_a_signed_link(): void
    {
        $content = $this->upload()['content'];
        $this->postJson(route('public.forms.submit', $this->form), ['data' => ['photo' => $this->answer($content)]])->assertOk();
        $submission = Submission::withoutGlobalScopes()->sole();
        $upload = $submission->uploads()->sole();

        // by its question and place (export links) or by the upload (the side panel)
        foreach ([
            route('forms.responses.file', [$this->form, $submission, 'question' => 'photo', 'file' => 0]),
            route('forms.responses.upload', [$this->form, $submission, $upload]),
        ] as $link) {
            $signed = $this->actingAs($this->owner)->get($link)->assertRedirect()->headers->get('Location');
            $this->assertStringContainsString(route('files.upload', $upload->hash), $signed);
            $this->assertSame('PNGDATA', $this->get($signed)->assertOk()->streamedContent());
        }

        // someone who can't see the form gets nothing; an upload of another response isn't found through this one
        $this->actingAs(tap(User::factory()->create(), fn (User $user) => [$this->workspace->addMember($user, WorkspaceRole::Member), $user->switchWorkspace($this->workspace)]))
            ->get(route('forms.responses.upload', [$this->form, $submission, $upload]))->assertForbidden();
        $other = Submission::factory()->to($this->form)->create();
        $this->actingAs($this->owner)->get(route('forms.responses.upload', [$this->form, $other, $upload]))->assertNotFound();
    }

    public function test_files_in_s3_or_r2_open_through_that_storages_own_signed_link(): void
    {
        foreach (['s3', 'r2'] as $driver) {
            Settings::put('storage.driver', $driver);
            $json = $this->upload('report.pdf', '%PDF');
            $upload = Upload::withoutGlobalScopes()->latest('id')->first();

            $this->assertSame($driver, $upload->disk);
            $this->assertSame('%PDF', FileStorage::disk($driver)->get($upload->path));
            $location = $this->get($json['preview'])->assertRedirect()->headers->get('Location');
            $this->assertStringStartsWith("https://{$driver}.storage.test/{$upload->path}", $location);
            $this->assertStringContainsString('ResponseContentDisposition', $location);
        }
    }

    public function test_switching_storage_leaves_earlier_files_where_they_were(): void
    {
        $before = $this->upload('before.png', 'OLD');
        Settings::put('storage.driver', 'r2');
        $this->upload('after.png', 'NEW');

        [$old, $new] = Upload::withoutGlobalScopes()->orderBy('id')->get()->all();
        $this->assertSame(['local', 'r2'], [$old->disk, $new->disk]);
        $this->assertSame('OLD', $this->get($before['preview'])->assertOk()->streamedContent());
    }

    public function test_uploads_never_sent_are_pruned_with_their_files_after_a_day(): void
    {
        $orphan = Upload::factory()->to($this->form)->stored()->create(['created_at' => now()->subHours(Upload::ORPHAN_HOURS + 1)]);
        $fresh = Upload::factory()->to($this->form)->stored()->create();
        $sent = Upload::factory()->sentWith(Submission::factory()->to($this->form)->create())->stored()->create(['created_at' => now()->subDays(3)]);

        $this->artisan('model:prune', ['--model' => [Upload::class]])->assertSuccessful();

        $this->assertModelMissing($orphan);
        $this->assertFalse(FileStorage::disk('local')->exists($orphan->path));
        $this->assertModelExists($fresh);
        $this->assertModelExists($sent);
    }

    public function test_purging_responses_or_deleting_the_form_deletes_their_files(): void
    {
        $sent = Upload::factory()->sentWith(Submission::factory()->to($this->form)->create())->stored()->create();
        $this->actingAs($this->owner)->postJson(route('forms.responses.purge', $this->form))->assertOk();
        $this->assertModelMissing($sent);
        $this->assertFalse(FileStorage::disk('local')->exists($sent->path));

        $waiting = Upload::factory()->to($this->form)->stored()->create();
        $this->form->delete();
        $this->assertFalse(FileStorage::disk('local')->exists($waiting->path));
        $this->assertSame(0, Upload::withoutGlobalScopes()->count());
    }

    public function test_exports_take_the_files_from_storage(): void
    {
        $submission = Submission::factory()->to($this->form)->create();
        $upload = Upload::factory()->sentWith($submission)->stored('STORED')->create(['name' => 'well.png']);
        $submission->update(['data' => ['photo' => $this->answer($upload->reference())]]);

        $file = $submission->fresh()->file('photo', 0);
        $this->assertTrue($upload->is($file['upload']));

        $this->assertSame('STORED', $upload->storage()->get($upload->path));
        $signed = URL::temporarySignedRoute('files.upload', now()->addMinutes(5), ['upload' => $upload->hash]);
        $this->assertSame('STORED', $this->get($signed)->streamedContent());
    }
}
