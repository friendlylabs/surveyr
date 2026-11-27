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
        // the charts on a Visualize tab: one question's summary, or a grouped chart, with its rules
        Schema::create('visualization_charts', function (Blueprint $table) {
            $table->id();
            $table->char('hash', 20)->unique()->comment('public route key, used in urls in place of the id');
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tab_id')->constrained('visualization_tabs')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->string('kind', 20)->comment('question | grouped');
            $table->string('title', 160)->nullable()->comment('shown in place of the question title');
            $table->json('rules')->comment('visual, sort, limit and what it charts, see App\Support\Analytics\ChartRules');
            $table->timestamps();

            $table->index(['tab_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visualization_charts');
    }
};
