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
        // number: the response's place in its form (1, 2, 3 ...), shown as RSP-12; archived_at: set aside out of the
        // list (responses are archived, not deleted; only a purge deletes them)
        Schema::table('submissions', function (Blueprint $table) {
            $table->unsignedInteger('number')->nullable()->after('form_id');
            $table->timestamp('archived_at')->nullable()->after('meta');
        });

        // number the responses already there, oldest first within each form
        DB::table('submissions')->select('form_id')->distinct()->pluck('form_id')->each(function (int $formId) {
            $number = 0;
            DB::table('submissions')->where('form_id', $formId)->orderBy('id')->pluck('id')
                ->each(function (int $id) use (&$number) {
                    DB::table('submissions')->where('id', $id)->update(['number' => ++$number]);
                });
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->unique(['form_id', 'number']);
            $table->index(['form_id', 'archived_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropUnique(['form_id', 'number']);
            $table->dropIndex(['form_id', 'archived_at']);
            $table->dropColumn(['number', 'archived_at']);
        });
    }
};
