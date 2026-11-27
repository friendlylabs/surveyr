<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Jobs\ExportResponses;
use App\Models\Form;
use App\Models\Submission;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\ResponsesExportReady;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use ZipArchive;

/**
 * ResponsesExportTest
 *
 * The responses as an Excel workbook (FormResponsesController@export, ResponsesExport): what the table lists, or the
 * ticked rows, with or without each response's details; with attachments, zipped in the background (ExportResponses)
 * and emailed as a signed link only its owner can use. Files are never public: a link from the app checks access,
 * then hands out a signed link that only works for a few minutes (Public\FilesController).
 */
class ResponsesExportTest extends TestCase
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
        $this->owner = $this->member();
        $this->form = Form::factory()->in($this->workspace)->create(['user_id' => $this->owner->id, 'title' => 'Water access', 'content' => ['pages' => [['name' => 'page1', 'elements' => [
            ['type' => 'text', 'name' => 'village', 'title' => 'Village name'],
            ['type' => 'radiogroup', 'name' => 'source', 'title' => 'Main water source', 'choices' => [['value' => 'bh', 'text' => 'Borehole'], ['value' => 'rv', 'text' => 'River']]],
            ['type' => 'file', 'name' => 'photo', 'title' => 'Photo'],
        ]]]]]);
    }

    private function member(WorkspaceRole $role = WorkspaceRole::Member): User
    {
        $user = User::factory()->create();
        $this->workspace->addMember($user, $role);
        $user->switchWorkspace($this->workspace);

        return $user;
    }

    /** A file answer as SurveyJS keeps it: a data URL in the answer. */
    private function file(string $name, string $type, string $bytes): array
    {
        return [['name' => $name, 'type' => $type, 'content' => "data:{$type};base64,".base64_encode($bytes)]];
    }

    /** The Responses sheet's XML from a downloaded workbook. */
    private function sheet(TestResponse $response): string
    {
        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true, 'The download is a valid .xlsx (zip) file.');
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        @unlink($path);

        return $xml;
    }

    public function test_the_export_holds_every_listed_response_with_readable_answers(): void
    {
        $response = Submission::factory()->to($this->form)->create(['data' => ['village' => 'Kongwa', 'source' => 'bh']]);
        Submission::factory()->to($this->form)->create(['data' => ['village' => 'Bahi', 'source' => 'rv']]);
        Submission::factory()->to($this->form)->create(['data' => ['village' => 'Archived town'], 'archived_at' => now()]);

        $download = $this->actingAs($this->owner)->post(route('forms.responses.export', $this->form), ['details' => 1])->assertOk()
            ->assertDownload('water-access-'.now()->format('Y-m-d').'.xlsx');
        $sheet = $this->sheet($download);

        $this->assertStringContainsString('Village name', $sheet);
        $this->assertStringContainsString('Kongwa', $sheet);
        $this->assertStringContainsString('Borehole', $sheet);
        $this->assertStringContainsString('River', $sheet);
        $this->assertStringNotContainsString('Archived town', $sheet);
        $this->assertStringContainsString('autoFilter', $sheet);
        // the reference links to the response in the app
        $this->assertStringContainsString(e(route('forms.responses.show', [$this->form, $response])), $sheet);
    }

    public function test_the_export_follows_the_search_and_the_archived_view(): void
    {
        Submission::factory()->to($this->form)->create(['data' => ['village' => 'Kongwa']]);
        Submission::factory()->to($this->form)->create(['data' => ['village' => 'Bahi']]);
        Submission::factory()->to($this->form)->create(['data' => ['village' => 'Old town'], 'archived_at' => now()]);

        $searched = $this->sheet($this->actingAs($this->owner)->post(route('forms.responses.export', [$this->form, 'q' => 'Kong'])));
        $this->assertStringContainsString('Kongwa', $searched);
        $this->assertStringNotContainsString('Bahi', $searched);

        $archived = $this->sheet($this->post(route('forms.responses.export', [$this->form, 'archived' => 1])));
        $this->assertStringContainsString('Old town', $archived);
        $this->assertStringNotContainsString('Kongwa', $archived);
    }

    public function test_only_the_ticked_responses_are_exported_when_some_are(): void
    {
        $ticked = Submission::factory()->to($this->form)->create(['data' => ['village' => 'Kongwa']]);
        Submission::factory()->to($this->form)->create(['data' => ['village' => 'Bahi']]);
        $elsewhere = Submission::factory()->create(['data' => ['village' => 'Elsewhere']]);

        $sheet = $this->sheet($this->actingAs($this->owner)->post(route('forms.responses.export', $this->form), ['responses' => [$ticked->hash, $elsewhere->hash]]));

        $this->assertStringContainsString('Kongwa', $sheet);
        $this->assertStringNotContainsString('Bahi', $sheet);
        $this->assertStringNotContainsString('Elsewhere', $sheet);
    }

    public function test_people_who_cannot_see_the_form_cannot_export_it(): void
    {
        $outsider = User::factory()->create();
        $otherWorkspace = Workspace::factory()->create();
        $otherWorkspace->addMember($outsider, WorkspaceRole::Member);
        $outsider->switchWorkspace($otherWorkspace);

        $this->actingAs($outsider)->post(route('forms.responses.export', $this->form))->assertNotFound();
        $this->actingAs($this->member())->post(route('forms.responses.export', $this->form))->assertForbidden();
    }

    public function test_a_file_links_through_the_app_never_to_a_public_url(): void
    {
        $response = Submission::factory()->to($this->form)->create(['data' => ['photo' => $this->file('well.png', 'image/png', 'PNGDATA')]]);

        $sheet = $this->sheet($this->actingAs($this->owner)->post(route('forms.responses.export', $this->form)));

        $this->assertStringContainsString('well.png', $sheet);
        $this->assertStringContainsString(e(route('forms.responses.file', [$this->form, $response, 'question' => 'photo', 'file' => 0])), $sheet);
        $this->assertStringNotContainsString('base64', $sheet);
    }

    public function test_without_details_the_rows_hold_only_the_answers(): void
    {
        Submission::factory()->to($this->form)->create(['data' => ['village' => 'Kongwa'], 'ip_address' => '203.0.113.9']);

        $plain = $this->sheet($this->actingAs($this->owner)->post(route('forms.responses.export', $this->form)));
        $this->assertStringContainsString('Kongwa', $plain);
        $this->assertStringNotContainsString('RSP-1', $plain);
        $this->assertStringNotContainsString('203.0.113.9', $plain);
        $this->assertStringNotContainsString('Collected by', $plain);

        $detailed = $this->sheet($this->post(route('forms.responses.export', $this->form), ['details' => 1]));
        $this->assertStringContainsString('RSP-1', $detailed);
        $this->assertStringContainsString('203.0.113.9', $detailed);
    }

    public function test_with_attachments_the_export_is_zipped_in_the_background_and_emailed(): void
    {
        Storage::fake('local');
        Notification::fake();
        Submission::factory()->to($this->form)->create(['data' => ['village' => 'Kongwa', 'photo' => $this->file('well.png', 'image/png', 'PNGDATA')]]);
        Submission::factory()->to($this->form)->create(['data' => ['village' => 'Bahi', 'photo' => [
            ...$this->file('a.pdf', 'application/pdf', 'PDF1'), ...$this->file('a.pdf', 'application/pdf', 'PDF2'),
        ]]]);

        $this->actingAs($this->owner)->postJson(route('forms.responses.export', $this->form), ['details' => 1, 'attachments' => 1])
            ->assertOk()->assertJsonPath('success', true)->assertJsonMissingPath('form');

        $url = null;
        Notification::assertSentTo($this->owner, ResponsesExportReady::class, function (ResponsesExportReady $notification) use (&$url) {
            $url = $notification->url;

            return str_contains($url, 'signature=');
        });

        // the link is the owner's alone
        $this->actingAs($this->member())->get($url)->assertNotFound();
        $download = $this->actingAs($this->owner)->get($url)->assertOk()->assertDownload('water-access-'.now()->format('Y-m-d').'.zip');

        $zipPath = tempnam(sys_get_temp_dir(), 'zip');
        file_put_contents($zipPath, $download->streamedContent());
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($zipPath) === true);
        $this->assertSame('PNGDATA', $zip->getFromName('attachments/RSP-1/photo/well.png'));
        $this->assertSame('PDF1', $zip->getFromName('attachments/RSP-2/photo/a.pdf'));
        $this->assertSame('PDF2', $zip->getFromName('attachments/RSP-2/photo/a (2).pdf'));
        $workbook = $zip->getFromName('water-access-'.now()->format('Y-m-d').'.xlsx');
        $zip->close();
        @unlink($zipPath);

        // the workbook links each file by its path in the zip, never to the app or a public URL
        $inner = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($inner, $workbook);
        $book = new ZipArchive;
        $book->open($inner);
        $sheet = $book->getFromName('xl/worksheets/sheet1.xml');
        $book->close();
        @unlink($inner);
        $this->assertStringContainsString('HYPERLINK(&quot;attachments/RSP-1/photo/well.png&quot;', $sheet);
        $this->assertStringContainsString('HYPERLINK(&quot;attachments/RSP-2/photo&quot;', $sheet);
        $this->assertStringNotContainsString('/file?', $sheet);
    }

    public function test_an_export_link_stops_working_once_it_expires_and_old_exports_are_pruned(): void
    {
        Storage::fake('local');
        Notification::fake();
        Submission::factory()->to($this->form)->create(['data' => ['photo' => $this->file('well.png', 'image/png', 'PNGDATA')]]);

        $this->actingAs($this->owner)->postJson(route('forms.responses.export', $this->form), ['attachments' => 1])->assertOk();
        $url = null;
        Notification::assertSentTo($this->owner, ResponsesExportReady::class, function (ResponsesExportReady $notification) use (&$url) {
            $url = $notification->url;

            return true;
        });
        $this->assertCount(1, Storage::disk('local')->allFiles(ExportResponses::FOLDER));

        $this->travel(ExportResponses::HOURS + 1)->hours();
        $this->get($url)->assertForbidden();
        ExportResponses::prune();
        $this->assertCount(0, Storage::disk('local')->allFiles(ExportResponses::FOLDER));
    }

    public function test_the_attachments_option_only_shows_for_a_form_with_file_questions(): void
    {
        Submission::factory()->to($this->form)->create();
        $this->actingAs($this->owner)->get(route('forms.responses', $this->form))->assertOk()->assertSee('data-export-attachments', false);

        $form = Form::factory()->in($this->workspace)->create(['user_id' => $this->owner->id, 'content' => ['pages' => [['name' => 'page1', 'elements' => [['type' => 'text', 'name' => 'village']]]]]]);
        Submission::factory()->to($form)->create();
        $this->get(route('forms.responses', $form))->assertOk()->assertSee('exportResponses', false)
            ->assertDontSee('data-export-attachments', false)->assertDontSee(__('Attachments'));
    }

    public function test_attachments_are_ignored_for_a_form_without_file_questions(): void
    {
        Notification::fake();
        $form = Form::factory()->in($this->workspace)->create(['user_id' => $this->owner->id, 'content' => ['pages' => [['name' => 'page1', 'elements' => [['type' => 'text', 'name' => 'village']]]]]]);
        Submission::factory()->to($form)->create(['data' => ['village' => 'Kongwa']]);

        $this->actingAs($this->owner)->post(route('forms.responses.export', $form), ['attachments' => 1])->assertOk()->assertDownload();
        Notification::assertNothingSent();
    }

    public function test_opening_a_file_checks_access_then_hands_out_a_short_lived_signed_link(): void
    {
        $response = Submission::factory()->to($this->form)->create(['data' => ['photo' => $this->file('well.png', 'image/png', 'PNGDATA')]]);
        $link = route('forms.responses.file', [$this->form, $response, 'question' => 'photo', 'file' => 0]);

        $this->get($link)->assertRedirect(route('auth.login'));
        $this->actingAs($this->member())->get($link)->assertForbidden();

        $signed = $this->actingAs($this->owner)->get($link)->assertRedirect()->headers->get('Location');
        $this->assertStringContainsString('signature=', $signed);
        $this->assertStringContainsString('expires=', $signed);

        // the signed link works without signing in, shows an image in the browser, and nothing it serves runs
        auth()->logout();
        $file = $this->get($signed)->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame('PNGDATA', $file->getContent());
        $this->assertStringStartsWith('inline', $file->headers->get('Content-Disposition'));
        $this->assertStringContainsString('sandbox', $file->headers->get('Content-Security-Policy'));

        // tampered with, or past its time, it's refused
        $this->get(str_replace('file=0', 'file=1', $signed))->assertForbidden();
        $this->travel(6)->minutes();
        $this->get($signed)->assertForbidden();
    }

    public function test_files_that_could_run_in_the_browser_download_instead(): void
    {
        $response = Submission::factory()->to($this->form)->create(['data' => ['photo' => $this->file('page.html', 'text/html', '<script>alert(1)</script>')]]);
        $signed = URL::temporarySignedRoute('files.response', now()->addMinutes(5), ['submission' => $response->hash, 'question' => 'photo', 'file' => 0]);

        $file = $this->get($signed)->assertOk()->assertHeader('Content-Type', 'application/octet-stream');
        $this->assertStringStartsWith('attachment', $file->headers->get('Content-Disposition'));
    }

    public function test_a_missing_file_is_not_found(): void
    {
        $response = Submission::factory()->to($this->form)->create(['data' => ['village' => 'Kongwa']]);

        $this->actingAs($this->owner)->get(route('forms.responses.file', [$this->form, $response, 'question' => 'photo', 'file' => 0]))->assertNotFound();
        $this->get(URL::temporarySignedRoute('files.response', now()->addMinutes(5), ['submission' => $response->hash, 'question' => 'photo', 'file' => 0]))->assertNotFound();
    }
}
