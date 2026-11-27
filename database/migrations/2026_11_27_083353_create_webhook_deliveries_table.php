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
        // each time a form's webhook is called (a new response, or a test), and how it went (App\Support\Webhooks);
        // kept for a while so the form's team can see what was sent and resend what failed, then pruned
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->char('hash', 20)->unique()->comment('public route key, sent with the request');
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submission_id')->nullable()->constrained()->nullOnDelete()->comment('the response it tells about; null for a test');
            $table->string('event', 40)->comment('response.created or test');
            $table->string('url', 2048)->comment('where it was sent');
            $table->string('status', 20)->default('pending')->comment('pending, retrying, delivered or failed');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedSmallInteger('response_code')->nullable()->comment('the last attempt\'s HTTP status');
            $table->string('response_body', 1000)->nullable()->comment('the start of the last attempt\'s reply');
            $table->string('error', 500)->nullable()->comment('why the last attempt failed, when it did');
            $table->unsignedInteger('duration_ms')->nullable()->comment('how long the last attempt took');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['form_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};
