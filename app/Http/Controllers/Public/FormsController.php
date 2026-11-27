<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\Submission;
use App\Models\Upload;
use App\Support\FileStorage;
use App\Support\Webhooks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * FormsController (public)
 *
 * The form as respondents reach it, at /form/{hash}, signed in or not: the form in its theme while it's open, or a page
 * saying why it isn't (archived, not open yet, closed); a form with an access code asks for it first, once per
 * browser session. Sending it stores a Submission. Files are uploaded as they're picked, to private storage
 * (FileStorage); the answer refers to each as "upload:{hash}", and sending the response claims those this browser
 * uploaded. The form's webhook, if it has one, is told about each response (Webhooks).
 */
class FormsController extends Controller
{
    /** The largest response that may be sent, in bytes of JSON. */
    const MAX_RESPONSE_BYTES = 1_000_000;

    /** How long the link previewing a file just uploaded works, in minutes. */
    const PREVIEW_MINUTES = 60;

    /** @var list<string> the uploads the response being sent refers to and may claim */
    private array $claimed = [];

    /** The form, or why it can't be filled in. */
    public function show(Request $request, Form $form): View
    {
        $this->form = $form;
        $this->state = $this->state($request, $form);

        return view('public.forms.show', $this->data);
    }

    /** Checks the access code; the right one opens the form for the rest of the browser session. */
    public function unlock(Request $request, Form $form): RedirectResponse
    {
        $code = (string) $request->input('access_code');

        if (! $form->access_code || ! hash_equals($form->access_code, $code)) {
            return back()->withErrors(['access_code' => __('That code isn\'t right. Check it and try again.')]);
        }

        $request->session()->put($this->sessionKey($form), true);

        return redirect()->route('public.forms.show', $form);
    }

    /** Stores a response while the form is open (and unlocked); the page shows SurveyJS's thank-you or redirect. */
    public function submit(Request $request, Form $form): JsonResponse
    {
        $state = $this->state($request, $form);
        if ($state !== 'open') {
            return $this->jsonError($state === 'locked' ? __('Enter the access code first.') : __('This form isn\'t taking responses.'), 403);
        }

        $data = $request->validate([
            'data' => ['present', 'array'],
            'meta' => ['nullable', 'array'],
            'meta.locale' => ['nullable', 'string', 'max:10'],
            'meta.seconds' => ['nullable', 'integer', 'min:0'],
        ]);

        if (strlen(json_encode($data['data'])) > self::MAX_RESPONSE_BYTES) {
            return $this->jsonError(__('This response is too large to send.'), 422);
        }

        $submission = new Submission([
            'data' => $data['data'],
            'meta' => array_filter(['locale' => $data['meta']['locale'] ?? null, 'seconds' => $data['meta']['seconds'] ?? null], fn ($value) => $value !== null),
        ]);
        $submission->workspace_id = $form->workspace_id;
        // staff collecting the form (signed in, in its workspace) are recorded; respondents stay anonymous
        $user = $request->user();
        $submission->collected_by = $user && $form->workspace->hasMember($user) ? $user->id : null;
        $submission->ip_address = $request->ip();
        $submission->form()->associate($form);
        // the form's row is locked while the response takes the next number; the files it refers to become its own
        DB::transaction(function () use ($request, $form, $submission) {
            Form::withoutGlobalScopes()->whereKey($form->id)->lockForUpdate()->value('id');
            $submission->data = $this->claimUploads($request, $form, $submission, $submission->data);
            $submission->save();
            Upload::withoutGlobalScopes()->whereIn('hash', $request->session()->pull($this->uploadsKey($form), []))
                ->where('form_id', $form->id)->whereNull('submission_id')->whereIn('hash', $this->claimed)
                ->update(['submission_id' => $submission->id]);
            // the form's webhook is told once the response is saved
            Webhooks::queue($form, $submission);
        });

        return $this->jsonSuccess(__('Response sent'));
    }

    /**
     * Takes one file for a file question while the form is open (SurveyJS uploads each as it's picked): kept privately
     * in the active storage, it belongs to no response until one is sent from this browser. Returns how the answer
     * refers to it and a link to preview it that works for an hour.
     */
    public function upload(Request $request, Form $form): JsonResponse
    {
        $state = $this->state($request, $form);
        if ($state !== 'open') {
            return $this->jsonError($state === 'locked' ? __('Enter the access code first.') : __('This form isn\'t taking responses.'), 403);
        }

        $request->validate(['file' => ['required', 'file', 'max:'.config('surveyr.uploads.max_kb')]]);
        $file = $request->file('file');

        $disk = FileStorage::active();
        $path = 'forms/'.$form->hash.'/'.Str::random(40);
        FileStorage::disk($disk)->putFileAs(dirname($path), $file, basename($path));

        $upload = new Upload([
            'disk' => $disk,
            'path' => $path,
            'name' => Str::limit($file->getClientOriginalName() ?: 'file', 250, ''),
            'type' => Str::limit($file->getMimeType() ?: 'application/octet-stream', 150, ''),
            'size' => $file->getSize(),
        ]);
        $upload->workspace_id = $form->workspace_id;
        $upload->form()->associate($form)->save();
        $request->session()->push($this->uploadsKey($form), $upload->hash);

        $this->data = [];
        $this->content = $upload->reference();
        $this->preview = URL::temporarySignedRoute('files.upload', now()->addMinutes(self::PREVIEW_MINUTES), ['upload' => $upload->hash]);

        return $this->jsonSuccess(__('File uploaded'));
    }

    /**
     * The answers with each file that refers to an upload ("upload:{hash}") kept only when this browser uploaded it to
     * this form and no response has it yet; references to any other are dropped. Notes those it keeps.
     */
    private function claimUploads(Request $request, Form $form, Submission $submission, array $data): array
    {
        $mine = (array) $request->session()->get($this->uploadsKey($form), []);
        $available = Upload::withoutGlobalScopes()->where('form_id', $form->id)->whereNull('submission_id')->whereIn('hash', $mine)->pluck('hash')->all();

        $walk = function (array $value) use (&$walk, $available): array {
            foreach ($value as $key => $item) {
                if (! is_array($item)) {
                    continue;
                }
                $hash = Upload::hashFrom($item['content'] ?? null);
                if ($hash !== null) {
                    if (in_array($hash, $available, true)) {
                        $this->claimed[] = $hash;
                    } else {
                        unset($value[$key]);
                    }

                    continue;
                }
                $value[$key] = $walk($item);
            }

            return array_is_list($value) ? array_values($value) : $value;
        };

        return $walk($data);
    }

    /** Where the session keeps the uploads this browser made to the form, until a response claims them. */
    private function uploadsKey(Form $form): string
    {
        return "forms.uploads.{$form->hash}";
    }

    /** open, locked (waiting for the access code), scheduled, closed or archived. */
    private function state(Request $request, Form $form): string
    {
        $status = $form->status();
        if ($status !== 'open') {
            return $status;
        }

        return $form->access_code && ! $request->session()->get($this->sessionKey($form)) ? 'locked' : 'open';
    }

    /** Where the session remembers that the form was unlocked. */
    private function sessionKey(Form $form): string
    {
        return "forms.unlocked.{$form->hash}";
    }

    /** Registers the public form routes (inside the /form prefix, routes/web.php). */
    public static function routes(): void
    {
        Route::get('/{form}', [self::class, 'show'])->name('public.forms.show');
        Route::post('/{form}/unlock', [self::class, 'unlock'])->middleware('throttle:10,1')->name('public.forms.unlock');
        Route::post('/{form}', [self::class, 'submit'])->middleware('throttle:30,1')->name('public.forms.submit');
        Route::post('/{form}/uploads', [self::class, 'upload'])->middleware('throttle:60,1')->name('public.forms.upload');
    }
}
