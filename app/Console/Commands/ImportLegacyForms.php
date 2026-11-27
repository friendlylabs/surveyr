<?php

namespace App\Console\Commands;

use App\Models\Form;
use App\Models\Space;
use App\Models\Submission;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ImportLegacyForms
 *
 * Brings the legacy forms (forms on the legacy connection) and their responses (collections + collection_payloads)
 * into one workspace. A form keeps md5(legacy id) as its legacy_key, so the legacy API, public links and
 * legacy:import-charts find it; a response keeps its collections.id.
 *
 *   title, description, content, opening and closing dates, access code, webhook and timestamps come over as they are
 *   (an "indefinite" form gets no closing date); the owner and collaborators are matched to this app's users by email,
 *   and the form's spaces to this workspace's spaces by name, so import or create those first
 *   responses keep their answers, timestamps and archived state, numbered in the order they came in
 *   left behind: themes (the app's own theme builder replaces SurveyJS's named themes), flags, the editing lock
 *
 * Safe to run again: a form already brought over is left as it is (it may have been changed since), and only its
 * responses not brought over yet are added, so a rehearsal can be topped up before the switch.
 */
#[Signature('legacy:import-forms
    {--workspace= : The workspace to import into (its hash); needed when there is more than one}
    {--form=* : Only these legacy form ids}
    {--dry-run : Report what would come over without writing anything}')]
#[Description('Bring the legacy forms and their responses into a workspace')]
class ImportLegacyForms extends Command
{
    /** Responses read and written per batch. */
    const CHUNK = 500;

    private ConnectionInterface $legacy;

    private Workspace $workspace;

    /** @var array<int, int> legacy user id => user id, matched by email */
    private array $users = [];

    /** @var array<int, int> legacy space id => space id, matched by name */
    private array $spaces = [];

    /** @var array{forms: int, existing: int, responses: int, owners: int, collaborators: int, spaces: int} */
    private array $counts = ['forms' => 0, 'existing' => 0, 'responses' => 0, 'owners' => 0, 'collaborators' => 0, 'spaces' => 0];

    /** Matches the legacy users and spaces, then walks the legacy forms in id order. */
    public function handle(): int
    {
        $workspace = $this->targetWorkspace();
        if (! $workspace) {
            return self::FAILURE;
        }
        $this->workspace = $workspace;
        $this->legacy = DB::connection('legacy');
        $this->matchUsers();
        $this->matchSpaces();
        $dryRun = (bool) $this->option('dry-run');

        $this->legacy->table('forms')
            ->when($this->option('form'), fn ($query, array $ids) => $query->whereIn('id', $ids))
            ->chunkById(100, fn (Collection $forms) => $forms->each(fn (object $row) => $this->importForm($row, $dryRun)));

        $this->table(['', __('Count')], [
            [__('Forms imported'), $this->counts['forms']],
            [__('Forms already imported'), $this->counts['existing']],
            [__('Responses imported'), $this->counts['responses']],
            [__('Owners not found (no owner set)'), $this->counts['owners']],
            [__('Collaborators not found'), $this->counts['collaborators']],
            [__('Spaces not found'), $this->counts['spaces']],
        ]);
        if ($dryRun) {
            $this->comment(__('Dry run: nothing was written.'));
        }

        return self::SUCCESS;
    }

    /** The workspace named by --workspace, or the only one there is. */
    private function targetWorkspace(): ?Workspace
    {
        $hash = $this->option('workspace');
        $workspace = $hash
            ? Workspace::where('hash', $hash)->first()
            : (Workspace::count() === 1 ? Workspace::first() : null);

        if (! $workspace) {
            $this->error($hash
                ? __('No workspace has the hash :hash.', ['hash' => $hash])
                : __('Name the workspace to import into with --workspace=<hash>.'));
        }

        return $workspace;
    }

    /** Legacy users to this app's users with the same email (any case). */
    private function matchUsers(): void
    {
        $emails = User::query()->pluck('id', 'email')->mapWithKeys(fn ($id, $email) => [mb_strtolower($email) => $id]);

        foreach ($this->legacy->table('users')->get(['id', 'email']) as $user) {
            if ($id = $emails->get(mb_strtolower(trim((string) $user->email)))) {
                $this->users[(int) $user->id] = $id;
            }
        }
    }

    /** Legacy spaces to this workspace's spaces with the same name (any case, outer spaces ignored). */
    private function matchSpaces(): void
    {
        $names = Space::withoutGlobalScope('workspace')->where('workspace_id', $this->workspace->id)->pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [mb_strtolower(trim($name)) => $id]);

        foreach ($this->legacy->table('spaces')->get(['id', 'name']) as $space) {
            if ($id = $names->get(mb_strtolower(trim((string) $space->name)))) {
                $this->spaces[(int) $space->id] = $id;
            }
        }
    }

    /** One legacy form: the form itself unless it's already here, then its responses not brought over yet. */
    private function importForm(object $row, bool $dryRun): void
    {
        $form = Form::withoutGlobalScope('workspace')->where('legacy_key', md5((string) $row->id))->first();

        if ($form) {
            $this->counts['existing']++;
        } else {
            $this->counts['forms']++;
            $form = $this->newForm($row, $dryRun);
        }

        $this->importResponses((int) $row->id, $form, $dryRun);
    }

    /** The form from its legacy row, with its owner, collaborators and spaces; not saved on a dry run. */
    private function newForm(object $row, bool $dryRun): Form
    {
        $owner = $this->users[(int) $row->user_id] ?? null;
        $collaborators = $this->matched($row->collaborators, $this->users, 'collaborators');
        $spaces = $this->matched($row->spaces, $this->spaces, 'spaces');
        if (! $owner) {
            $this->counts['owners']++;
        }
        $content = json_decode((string) $row->content, true);
        if (! is_array($content)) {
            $this->warn(__('Legacy form :id: its definition is not readable JSON; it comes over empty.', ['id' => $row->id]));
        }

        $form = new Form([
            'title' => mb_substr(trim((string) $row->title), 0, 255) ?: __('Untitled form'),
            'description' => $row->description === null ? null : mb_substr(trim($row->description), 0, 500),
            'content' => is_array($content) ? $content : null,
            'opens_at' => $row->start_date,
            'closes_at' => $row->is_indefinite ? null : $row->end_date,
            'webhook_url' => $row->webhook_url ?: null,
        ]);
        $form->legacy_key = md5((string) $row->id);
        $form->workspace_id = $this->workspace->id;
        $form->user_id = $owner;
        $form->access_code = $row->access_code ?: null;
        $form->created_at = $row->created_at;
        $form->updated_at = $row->updated_at ?? $row->created_at;

        if (! $dryRun) {
            DB::transaction(function () use ($form, $owner, $collaborators, $spaces) {
                $form->save();
                $form->collaborators()->attach(array_values(array_diff($collaborators, [$owner])));
                $form->spaces()->attach($spaces);
            });
        }

        return $form;
    }

    /**
     * The ids a legacy JSON list of ids (forms.collaborators, forms.spaces) matches here, counting those that don't.
     *
     * @param  array<int, int>  $matches
     * @return list<int>
     */
    private function matched(?string $list, array $matches, string $count): array
    {
        $ids = array_filter(array_map('intval', (array) json_decode((string) $list, true)));
        $found = array_values(array_unique(array_filter(array_map(fn (int $id) => $matches[$id] ?? null, $ids))));
        $this->counts[$count] += count($ids) - count(array_filter($ids, fn (int $id) => isset($matches[$id])));

        return $found;
    }

    /** A legacy form's responses not brought over yet, in the order they came in, numbered after the form's last. */
    private function importResponses(int $legacyId, Form $form, bool $dryRun): void
    {
        $imported = $form->exists
            ? Submission::withoutGlobalScope('workspace')->where('form_id', $form->id)->whereNotNull('legacy_key')->pluck('legacy_key')->flip()
            : collect();
        $number = $form->exists ? (int) Submission::withoutGlobalScope('workspace')->where('form_id', $form->id)->max('number') : 0;

        $this->legacy->table('collections')
            ->leftJoin('collection_payloads', 'collection_payloads.collection_id', '=', 'collections.id')
            ->where('collections.form_id', $legacyId)
            ->select(['collections.id', 'collections.is_archived', 'collections.created_at', 'collections.updated_at', 'collection_payloads.submission'])
            ->chunkById(self::CHUNK, function (Collection $rows) use ($form, $imported, &$number, $dryRun) {
                $rows = $rows->reject(fn (object $row) => $imported->has($row->id));
                $this->counts['responses'] += $rows->count();
                if ($dryRun || $rows->isEmpty()) {
                    return;
                }

                DB::transaction(function () use ($rows, $form, &$number) {
                    foreach ($rows as $row) {
                        $data = json_decode((string) $row->submission, true);
                        $submission = new Submission(['data' => is_array($data) ? $data : []]);
                        $submission->legacy_key = $row->id;
                        $submission->workspace_id = $form->workspace_id;
                        $submission->form_id = $form->id;
                        $submission->number = ++$number;
                        $submission->created_at = $row->created_at;
                        $submission->updated_at = $row->updated_at ?? $row->created_at;
                        $submission->archived_at = $row->is_archived ? ($row->updated_at ?? $row->created_at) : null;
                        $submission->save();
                    }
                });
            }, 'collections.id', 'id');
    }
}
