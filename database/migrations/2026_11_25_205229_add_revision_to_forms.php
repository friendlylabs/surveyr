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
        // counts saves of the form's content: the builder sends the revision it loaded, so one editor's autosave can't
        // silently overwrite another's (FormsController::saveContent)
        Schema::table('forms', function (Blueprint $table) {
            $table->unsignedInteger('revision')->default(0)->after('content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn('revision');
        });
    }
};
