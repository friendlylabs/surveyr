<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Jobs\ExportResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ExportsController
 *
 * Downloads of exports made in the background (ExportResponses), from the link emailed when one is ready: signed and
 * timed, and only for the person who asked for it, signed in.
 */
class ExportsController extends Controller
{
    /** One of the signed-in person's exports (?name= the file name it downloads as). */
    public function download(Request $request, string $export): StreamedResponse
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{32}$/', $export) === 1, 404);

        $path = ExportResponses::FOLDER.'/'.$request->user()->hash.'/'.$export.'.zip';
        abort_unless(Storage::disk('local')->exists($path), 404);

        $name = preg_replace('/[^\w.-]+/', '-', (string) $request->query('name')) ?: 'responses.zip';

        return Storage::disk('local')->download($path, str_ends_with($name, '.zip') ? $name : $name.'.zip');
    }

    /** Registers the export routes (inside the /app/exports prefix, routes/app.php). */
    public static function routes(): void
    {
        Route::get('/{export}', [self::class, 'download'])->middleware('signed')->name('exports.download');
    }
}
