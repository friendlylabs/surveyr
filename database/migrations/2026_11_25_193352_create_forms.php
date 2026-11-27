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
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->char('hash', 20)->unique()->comment('public route key, used in urls in place of the id');
            $table->char('legacy_key', 32)->nullable()->unique()->comment('md5(legacy id): the legacy API and public links address forms by it');
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->comment('owner')->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('description', 500)->nullable();
            $table->string('slug');
            $table->json('content')->nullable()->comment('the SurveyJS form definition');
            $table->string('theme', 50)->nullable();
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->text('access_code')->nullable()->comment('encrypted; respondents must enter it');
            $table->string('webhook_url', 2048)->nullable();
            $table->json('settings')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['workspace_id', 'updated_at']);
        });

        // a form can sit in several spaces; its access is the union of the user's roles across them
        Schema::create('form_space', function (Blueprint $table) {
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->foreignId('space_id')->constrained()->cascadeOnDelete();

            $table->primary(['form_id', 'space_id']);
            $table->index('space_id');
        });

        // people the owner shares the form with, outside any space
        Schema::create('form_user', function (Blueprint $table) {
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['form_id', 'user_id']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_user');
        Schema::dropIfExists('form_space');
        Schema::dropIfExists('forms');
    }
};
