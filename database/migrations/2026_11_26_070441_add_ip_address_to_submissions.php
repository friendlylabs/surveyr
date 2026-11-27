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
        // the address the response was sent from (IPv4 or IPv6); null for responses from before it was recorded
        Schema::table('submissions', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('collected_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn('ip_address');
        });
    }
};
