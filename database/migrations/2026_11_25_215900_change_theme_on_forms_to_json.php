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
        // the builder's theme choices (style, layout, palette or custom colour, mode, font, background image); the
        // SurveyJS theme is built from them when the form is shown (resources/js/surveyjs/theme.js)
        Schema::table('forms', function (Blueprint $table) {
            $table->json('theme')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->string('theme', 50)->nullable()->change();
        });
    }
};
