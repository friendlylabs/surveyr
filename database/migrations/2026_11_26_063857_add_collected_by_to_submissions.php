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
        // the staff member who collected the response (signed in, in the form's workspace); null for respondents who
        // filled the form in themselves. Kept when the account goes: users are disabled, not deleted
        Schema::table('submissions', function (Blueprint $table) {
            $table->foreignId('collected_by')->nullable()->after('form_id')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('collected_by');
        });
    }
};
