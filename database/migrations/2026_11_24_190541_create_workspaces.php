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
        // the tenant: every form, space, report and zone carries a workspace_id (App\Models\Concerns\BelongsToWorkspace)
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->char('hash', 20)->unique()->comment('public route key, used in urls in place of the id');
            $table->string('name');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // membership, with the member's role in that workspace (App\Enums\WorkspaceRole)
        Schema::create('workspace_user', function (Blueprint $table) {
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['workspace_id', 'user_id']);
            $table->index('user_id');
        });

        // the workspace the user is working in; emptied (not cascaded) when it is deleted
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('current_workspace_id')->nullable()->after('role_id')->constrained('workspaces')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_workspace_id']);
            $table->dropColumn('current_workspace_id');
        });

        Schema::dropIfExists('workspace_user');
        Schema::dropIfExists('workspaces');
    }
};
