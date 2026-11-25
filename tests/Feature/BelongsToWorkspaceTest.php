<?php

namespace Tests\Feature;

use App\Models\Concerns\BelongsToWorkspace;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * BelongsToWorkspaceTest
 *
 * The tenancy scope every workspace data model uses, on a throwaway table so it is checked before any real model
 * has it.
 */
class BelongsToWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $mine;

    private Workspace $theirs;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('scoped_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained();
            $table->string('body');
        });

        $this->mine = Workspace::factory()->create();
        $this->theirs = Workspace::factory()->create();
    }

    private function current(): CurrentWorkspace
    {
        return app(CurrentWorkspace::class);
    }

    public function test_queries_only_see_the_current_workspace(): void
    {
        ScopedNote::forceCreate(['workspace_id' => $this->mine->id, 'body' => 'mine']);
        ScopedNote::forceCreate(['workspace_id' => $this->theirs->id, 'body' => 'theirs']);

        $this->current()->set($this->mine);

        $this->assertSame(['mine'], ScopedNote::pluck('body')->all());
        $this->assertNull(ScopedNote::where('body', 'theirs')->first());
        $this->assertSame(2, ScopedNote::withoutGlobalScope('workspace')->count());
    }

    public function test_new_rows_are_filled_with_the_current_workspace(): void
    {
        $this->current()->set($this->mine);

        $note = ScopedNote::create(['body' => 'hello']);

        $this->assertSame($this->mine->id, $note->workspace_id);
        $this->assertTrue($note->workspace->is($this->mine));
    }

    public function test_run_works_inside_a_workspace_then_restores_the_previous_one(): void
    {
        ScopedNote::forceCreate(['workspace_id' => $this->theirs->id, 'body' => 'theirs']);
        $this->current()->set($this->mine);

        $count = $this->current()->run($this->theirs, fn () => ScopedNote::count());

        $this->assertSame(1, $count);
        $this->assertSame($this->mine->id, $this->current()->id());
    }

    public function test_the_workspace_resets_between_requests(): void
    {
        $this->current()->set($this->mine);

        $this->app->forgetScopedInstances();

        $this->assertNull(app(CurrentWorkspace::class)->get());
    }
}

class ScopedNote extends Model
{
    use BelongsToWorkspace;

    protected $table = 'scoped_notes';

    protected $fillable = ['body'];

    public $timestamps = false;
}
