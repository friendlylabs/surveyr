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
        // a form's saved views of its responses (legacy collection_filters): a name and the filter rules, shared with
        // everyone who can see the form
        Schema::create('submission_views', function (Blueprint $table) {
            $table->id();
            $table->char('hash', 20)->unique()->comment('public route key, used in urls in place of the id');
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->comment('who saved it');
            $table->string('name', 120);
            $table->string('description', 300)->nullable();
            $table->json('filters')->comment('{from, to, rules: [{question, operator, value}]}, see App\Support\SubmissionFilters');
            $table->timestamps();

            $table->index(['form_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submission_views');
    }
};
