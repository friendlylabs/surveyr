<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // a project inside a workspace: groups forms and decides who can work on them
        Schema::create('spaces', function (Blueprint $table) {
            $table->id();
            $table->char('hash', 20)->unique()->comment('public route key, used in urls in place of the id');
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('color', 20)->nullable();
            $table->foreignId('user_id')->nullable()->comment('who created it')->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['workspace_id', 'name']);
        });

        // membership, with the member's role in that space (App\Enums\SpaceRole)
        Schema::create('space_user', function (Blueprint $table) {
            $table->foreignId('space_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['space_id', 'user_id']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('space_user');
        Schema::dropIfExists('spaces');
    }
};
