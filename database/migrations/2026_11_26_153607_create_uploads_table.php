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
        // files respondents upload to a form, kept privately in the storage chosen at the time (App\Support\FileStorage);
        // answers refer to them as "upload:{hash}". Until its response is sent an upload belongs to no submission, and
        // those left that way are pruned
        Schema::create('uploads', function (Blueprint $table) {
            $table->id();
            $table->char('hash', 20)->unique()->comment('public route key, used in answers and urls in place of the id');
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submission_id')->nullable()->constrained()->nullOnDelete()->comment('the response it was sent with; null until then');
            $table->string('disk', 20)->comment('where it is kept: local, s3 or r2');
            $table->string('path')->comment('its key in that storage');
            $table->string('name')->comment('the file name it was uploaded with');
            $table->string('type', 150)->comment('its media type');
            $table->unsignedBigInteger('size')->comment('bytes');
            $table->timestamps();

            $table->index(['submission_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uploads');
    }
};
