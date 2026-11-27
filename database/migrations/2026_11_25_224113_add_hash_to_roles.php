<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // roles reach the page (the users screens), so they get a public hash like every such record (HasHash);
        // existing roles are filled in
        Schema::table('roles', function (Blueprint $table) {
            $table->char('hash', 20)->nullable()->after('id');
        });
        DB::table('roles')->whereNull('hash')->orderBy('id')->each(function ($role) {
            DB::table('roles')->where('id', $role->id)->update(['hash' => bin2hex(random_bytes(10))]);
        });
        Schema::table('roles', function (Blueprint $table) {
            $table->char('hash', 20)->nullable(false)->unique()->comment('public route key, used on pages in place of the id')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['hash']);
            $table->dropColumn('hash');
        });
    }
};
