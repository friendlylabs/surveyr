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
        // a form's responses ("submissions"; legacy collections + collection_payloads): the answers as SurveyJS
        // gives them, and a little about how they came in
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->char('hash', 20)->unique()->comment('public route key, used in urls in place of the id');
            $table->unsignedInteger('legacy_key')->nullable()->unique()->comment('collections.id in the legacy app, for the import');
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->json('data');
            $table->json('meta')->nullable()->comment('language, time taken');
            $table->timestamps();

            $table->index(['form_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
