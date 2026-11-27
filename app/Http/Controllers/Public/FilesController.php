<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\Upload;
use App\Support\FileStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * FilesController (public)
 *
 * Uploaded files through short-lived signed URLs only: nothing uploaded is public. The app checks who may see a file
 * (FormResponsesController@file, or the uploader's own preview) and hands out a link to here that works for a few
 * minutes. A file kept in S3 or R2 is passed on to that storage's own signed link (a few minutes too); one kept here
 * is sent by the app. Images and PDFs open in the browser; anything else downloads, and nothing served runs scripts
 * (sandboxed, never sniffed).
 */
class FilesController extends Controller
{
    /** Types shown in the browser; the rest download. */
    const INLINE = ['image/png', 'image/jpeg', 'image/gif', 'image/webp', 'application/pdf'];

    /** How long a storage's own signed link works, in minutes. */
    const STORAGE_LINK_MINUTES = 5;

    /** An uploaded file, from the storage it's kept in. */
    public function upload(string $upload): StreamedResponse|RedirectResponse
    {
        $file = Upload::withoutGlobalScopes()->where('hash', $upload)->firstOrFail();
        [$type, $disposition] = $this->presented($file->type, $file->name);

        if (FileStorage::signsUrls($file->disk)) {
            return redirect()->away($file->storage()->temporaryUrl($file->path, now()->addMinutes(self::STORAGE_LINK_MINUTES), [
                'ResponseContentType' => $type,
                'ResponseContentDisposition' => $disposition,
            ]));
        }

        abort_unless($file->storage()->exists($file->path), 404);

        return $file->storage()->response($file->path, $file->name, $this->headers($type, $disposition));
    }

    /** One file kept in a response's answer itself (a data URL: signatures, older responses; ?question=, ?file=). */
    public function response(Request $request, string $submission): Response
    {
        $response = Submission::withoutGlobalScopes()->where('hash', $submission)->firstOrFail();
        $file = $response->file((string) $request->query('question'), (int) $request->query('file'));
        abort_unless($file && isset($file['bytes']), 404);
        [$type, $disposition] = $this->presented($file['type'], $file['name']);

        return response($file['bytes'], 200, $this->headers($type, $disposition));
    }

    /**
     * How a file is sent: its type and disposition (inline for the safe types, otherwise a download).
     *
     * @return array{0: string, 1: string}
     */
    private function presented(string $type, string $name): array
    {
        $inline = in_array($type, self::INLINE, true);
        $fallback = str($name)->ascii()->replace(['/', '\\', '%', '"'], '-')->value() ?: 'file';

        return [$inline ? $type : 'application/octet-stream', HeaderUtils::makeDisposition($inline ? 'inline' : 'attachment', $name, $fallback)];
    }

    /** @return array<string, string> */
    private function headers(string $type, string $disposition): array
    {
        return [
            'Content-Type' => $type,
            'Content-Disposition' => $disposition,
            'Content-Security-Policy' => "default-src 'none'; img-src data:; style-src 'unsafe-inline'; sandbox",
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=300',
        ];
    }

    /** Registers the file routes (inside the /files prefix, routes/web.php); each needs a valid, unexpired signature. */
    public static function routes(): void
    {
        Route::get('/uploads/{upload}', [self::class, 'upload'])->middleware('signed')->name('files.upload');
        Route::get('/responses/{submission}', [self::class, 'response'])->middleware('signed')->name('files.response');
    }
}
