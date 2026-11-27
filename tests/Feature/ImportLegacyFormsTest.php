<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\Space;
use App\Models\Submission;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * ImportLegacyFormsTest
 *
 * legacy:import-forms: legacy forms and their responses coming into a workspace, read from a stand-in legacy database
 * (in-memory SQLite users, spaces, forms, collections and collection_payloads tables). Owners, collaborators and spaces
 * are matched by email and name; running it again only adds responses not brought over yet.
 */
class ImportLegacyFormsTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

    private User $owner;

    private User $collaborator;

    private Space $space;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.legacy' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('legacy');
        $schema = Schema::connection('legacy');
        $schema->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email');
        });
        $schema->create('spaces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        $schema->create('forms', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->text('collaborators')->nullable();
            $table->text('spaces')->nullable();
            $table->boolean('is_indefinite')->default(false);
            $table->string('webhook_url')->nullable();
            $table->string('access_code')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamps();
        });
        $schema->create('collections', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('form_id');
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
        });
        $schema->create('collection_payloads', function (Blueprint $table) {
            $table->unsignedInteger('collection_id');
            $table->longText('submission');
        });

        $this->workspace = Workspace::factory()->create();
        $this->owner = User::factory()->create(['email' => 'owner@example.org']);
        $this->collaborator = User::factory()->create(['email' => 'helper@example.org']);
        $this->space = Space::factory()->in($this->workspace)->create(['name' => 'ICT Club']);
        $legacy = DB::connection('legacy');
        $legacy->table('users')->insert([
            ['id' => 1, 'email' => 'Owner@Example.org'],
            ['id' => 2, 'email' => 'helper@example.org'],
            ['id' => 3, 'email' => 'gone@example.org'],
        ]);
        $legacy->table('spaces')->insert([['id' => 1, 'name' => 'ICT Club '], ['id' => 2, 'name' => 'Nowhere']]);
    }

    /** A legacy form (id 7 unless given), with these columns over the defaults. */
    private function legacyForm(array $columns = []): int
    {
        return DB::connection('legacy')->table('forms')->insertGetId([
            'id' => 7,
            'title' => ' Water points ',
            'description' => 'Where people draw water',
            'content' => json_encode(['pages' => [['elements' => [['type' => 'text', 'name' => 'village']]]]]),
            'user_id' => 1,
            'collaborators' => '["1", "2", "3"]',
            'spaces' => '["1", "2"]',
            'is_indefinite' => false,
            'webhook_url' => null,
            'access_code' => 'river',
            'start_date' => '2025-01-01 08:00:00',
            'end_date' => '2025-02-01 08:00:00',
            'created_at' => '2024-12-20 10:00:00',
            'updated_at' => '2024-12-21 10:00:00',
            ...$columns,
        ]);
    }

    /** A legacy response to the form, with its answers. */
    private function legacyResponse(int $id, int $formId, array $data, bool $archived = false): void
    {
        DB::connection('legacy')->table('collections')->insert(['id' => $id, 'form_id' => $formId, 'is_archived' => $archived, 'created_at' => '2025-01-0'.min($id, 9).' 09:00:00', 'updated_at' => '2025-01-10 09:00:00']);
        DB::connection('legacy')->table('collection_payloads')->insert(['collection_id' => $id, 'submission' => json_encode($data)]);
    }

    /** The form imported from legacy form 7. */
    private function imported(int $legacyId = 7): ?Form
    {
        return Form::withoutGlobalScopes()->where('legacy_key', md5((string) $legacyId))->first();
    }

    public function test_it_imports_a_form_with_its_owner_collaborators_spaces_and_responses(): void
    {
        $this->legacyForm();
        $this->legacyResponse(11, 7, ['village' => 'Kibaha']);
        $this->legacyResponse(12, 7, ['village' => 'Mlandizi'], archived: true);

        $this->artisan('legacy:import-forms')->assertSuccessful();

        $form = $this->imported();
        $this->assertSame('Water points', $form->title);
        $this->assertSame($this->workspace->id, $form->workspace_id);
        $this->assertSame($this->owner->id, $form->user_id);
        $this->assertSame('village', $form->content['pages'][0]['elements'][0]['name']);
        $this->assertSame('river', $form->access_code);
        $this->assertSame('2025-02-01 08:00:00', $form->closes_at->format('Y-m-d H:i:s'));
        $this->assertSame('2024-12-20 10:00:00', $form->created_at->format('Y-m-d H:i:s'));
        $this->assertSame([$this->collaborator->id], $form->collaborators()->pluck('users.id')->all());
        $this->assertSame([$this->space->id], $form->spaces()->pluck('spaces.id')->all());

        $responses = Submission::withoutGlobalScopes()->where('form_id', $form->id)->orderBy('number')->get();
        $this->assertSame([11, 12], $responses->pluck('legacy_key')->all());
        $this->assertSame([1, 2], $responses->pluck('number')->all());
        $this->assertSame('Mlandizi', $responses[1]->data['village']);
        $this->assertNull($responses[0]->archived_at);
        $this->assertNotNull($responses[1]->archived_at);
    }

    public function test_an_indefinite_form_has_no_closing_date_and_an_unknown_owner_leaves_it_ownerless(): void
    {
        $this->legacyForm(['is_indefinite' => true, 'user_id' => 3]);

        $this->artisan('legacy:import-forms')->assertSuccessful();

        $this->assertNull($this->imported()->closes_at);
        $this->assertNull($this->imported()->user_id);
    }

    public function test_running_it_again_keeps_the_form_and_only_adds_new_responses(): void
    {
        $this->legacyForm();
        $this->legacyResponse(11, 7, ['village' => 'Kibaha']);
        $this->artisan('legacy:import-forms')->assertSuccessful();
        $this->imported()->update(['title' => 'Renamed here']);
        $this->legacyResponse(12, 7, ['village' => 'Mlandizi']);

        $this->artisan('legacy:import-forms')->assertSuccessful();

        $this->assertSame(1, Form::withoutGlobalScopes()->count());
        $this->assertSame('Renamed here', $this->imported()->title);
        $this->assertSame([11 => 1, 12 => 2], Submission::withoutGlobalScopes()->pluck('number', 'legacy_key')->all());
    }

    public function test_a_dry_run_writes_nothing(): void
    {
        $this->legacyForm();
        $this->legacyResponse(11, 7, ['village' => 'Kibaha']);

        $this->artisan('legacy:import-forms', ['--dry-run' => true])->assertSuccessful();

        $this->assertNull($this->imported());
        $this->assertSame(0, Submission::withoutGlobalScopes()->count());
    }

    public function test_it_only_imports_the_forms_asked_for(): void
    {
        $this->legacyForm();
        $this->legacyForm(['id' => 8]);

        $this->artisan('legacy:import-forms', ['--form' => [8]])->assertSuccessful();

        $this->assertNull($this->imported(7));
        $this->assertNotNull($this->imported(8));
    }

    public function test_with_several_workspaces_it_needs_the_one_to_import_into(): void
    {
        $other = Workspace::factory()->create();
        $this->legacyForm();

        $this->artisan('legacy:import-forms')->assertFailed();
        $this->assertNull($this->imported());

        $this->artisan('legacy:import-forms', ['--workspace' => $other->hash])->assertSuccessful();
        $this->assertSame($other->id, $this->imported()->workspace_id);
    }
}
