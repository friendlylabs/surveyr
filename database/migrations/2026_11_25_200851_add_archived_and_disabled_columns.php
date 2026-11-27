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
        // users are disabled, never deleted: a disabled account can't sign in, its work stays
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('disabled_at')->nullable()->after('locale');
        });

        // a form with responses is archived, not deleted: it leaves the list and stops collecting
        Schema::table('forms', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('closes_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('disabled_at');
        });

        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });
    }
};
