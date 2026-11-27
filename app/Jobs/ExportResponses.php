<?php

namespace App\Jobs;

use App\Exceptions\ExportFailed;
use App\Models\Form;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\ResponsesExportFailed;
use App\Notifications\ResponsesExportReady;
use App\Support\AttachmentsExport;
use App\Support\ResponsesExport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;
use ZipArchive;

/**
 * ExportResponses
 *
 * Exports made in the background, zipped: the workbook with every file it links to beside it (ResponsesExport, the
 * links working once it's unpacked), or (ATTACHMENTS) just the files, as the legacy app's attachments export did
 * (AttachmentsExport: RSP-12/question/file and a manifest). The zip is kept privately (local disk,
 * exports/{user hash}/) and the person who asked gets an email with a signed link to it (ExportsController) that lasts
 * HOURS hours; older exports are pruned (routes/console.php). If it can't be made, they get an email saying why.
 */
class ExportResponses implements ShouldQueue
{
    use Queueable;

    /** What's exported: the workbook with its attachments, or the attachments alone. */
    const WORKBOOK = 'workbook';

    const ATTACHMENTS = 'attachments';

    /** How long an export (and the emailed link to it) is kept, in hours. */
    const HOURS = 24;

    /** Where exports are kept on the local (private) disk. */
    const FOLDER = 'exports';

    /** One try: a failure is reported by email, and asking again is a click away. */
    public $tries = 1;

    /** Seconds it may run. */
    public $timeout = 1800;

    /**
     * @param  list<int>  $responses  the responses' ids, in the order they're listed
     * @param  array<string, string>  $about  what was exported, for the workbook's About sheet
     * @param  bool  $details  the workbook's rows start with each response's details
     * @param  string  $kind  WORKBOOK or ATTACHMENTS
     */
    public function __construct(public User $user, public Form $form, public array $responses, public array $about = [], public bool $details = true, public string $kind = self::WORKBOOK) {}

    /** Makes the zip, keeps it, and emails the link. */
    public function handle(): void
    {
        $folder = storage_path('framework/cache/export-'.Str::random(20));
        File::ensureDirectoryExists($folder);
        $base = str($this->form->title)->slug()->limit(60, '')->value() ?: 'responses';

        try {
            if ($this->kind === self::ATTACHMENTS) {
                $attachments = new AttachmentsExport(AttachmentsExport::questions($this->form->content), $folder);
                foreach ($this->listed() as $response) {
                    $attachments->add($response);
                }
                if ($attachments->count() === 0) {
                    throw new ExportFailed(__('There are no attachments in these responses.'));
                }
                $attachments->writeManifest();
                $download = "{$base}-attachments-".now()->format('Y-m-d').'.zip';
            } else {
                $export = new ResponsesExport($this->form, $this->details, $folder, $this->user->name);
                rename($export->write($this->listed(), $this->about), $folder.'/'.$export->filename());
                $download = Str::beforeLast($export->filename(), '.xlsx').'.zip';
            }

            $name = Str::random(32);
            $path = self::FOLDER.'/'.$this->user->hash.'/'.$name.'.zip';
            Storage::disk('local')->makeDirectory(dirname($path));
            $this->zip($folder, Storage::disk('local')->path($path));
        } finally {
            File::deleteDirectory($folder);
        }

        $url = URL::temporarySignedRoute('exports.download', now()->addHours(self::HOURS), ['export' => $name, 'name' => $download]);
        $this->user->notify(new ResponsesExportReady($this->form->title, $url, self::HOURS, $this->kind === self::ATTACHMENTS));
    }

    /** Tells the person who asked that the export couldn't be made, and why when that's known. */
    public function failed(?Throwable $exception): void
    {
        $reason = $exception instanceof ExportFailed ? $exception->getMessage() : null;
        $this->user->notify(new ResponsesExportFailed($this->form->title, route('forms.responses', $this->form), $reason));
    }

    /**
     * The responses, a chunk at a time, in the order they were listed.
     *
     * @return iterable<Submission>
     */
    private function listed(): iterable
    {
        foreach (array_chunk($this->responses, 500) as $ids) {
            $chunk = Submission::withoutGlobalScopes()->whereIn('id', $ids)->where('form_id', $this->form->id)
                ->with(['collector', 'uploads'])->get()->keyBy('id');
            foreach ($ids as $id) {
                if ($chunk->has($id)) {
                    yield $chunk->get($id);
                }
            }
        }
    }

    /** Zips the folder's contents into the given file; files are stored as they are, being mostly compressed already. */
    private function zip(string $folder, string $target): void
    {
        $zip = new ZipArchive;
        if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new ExportFailed(__('The archive could not be created.'));
        }
        foreach (File::allFiles($folder) as $file) {
            $name = str_replace('\\', '/', $file->getRelativePathname());
            $zip->addFile($file->getPathname(), $name);
            if (! str_ends_with($name, '.csv') && ! str_ends_with($name, '.xlsx')) {
                $zip->setCompressionName($name, ZipArchive::CM_STORE);
            }
        }
        if (! $zip->close()) {
            throw new ExportFailed(__('The archive could not be written.'));
        }
    }

    /** Deletes exports older than HOURS hours (scheduled in routes/console.php). */
    public static function prune(): void
    {
        $disk = Storage::disk('local');
        foreach ($disk->allFiles(self::FOLDER) as $file) {
            if ($disk->lastModified($file) < now()->subHours(self::HOURS)->getTimestamp()) {
                $disk->delete($file);
            }
        }
    }
}
