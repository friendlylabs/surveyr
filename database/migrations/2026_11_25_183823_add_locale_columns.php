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
        // a user's interface language; null follows their workspace's default
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 10)->nullable()->after('current_workspace_id');
        });

        // the language new members start with; null follows the app default
        Schema::table('workspaces', function (Blueprint $table) {
            $table->string('locale', 10)->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale');
        });

        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
