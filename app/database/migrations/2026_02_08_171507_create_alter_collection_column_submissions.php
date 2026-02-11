<?php

use Leaf\Schema;
use Leaf\Database;
use Illuminate\Database\Schema\Blueprint;

class CreateAlterCollectionColumnSubmissions extends Database
{
    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        // Remove submission column from collections table
        if (static::$capsule::schema()->hasColumn('collections', 'submission')) {
            static::$capsule::schema()->table('collections', function (Blueprint $table) {
                $table->dropColumn('submission');
            });
        }
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down()
    {
        // Add back submission column to collections table
        if (!static::$capsule::schema()->hasColumn('collections', 'submission')) {
            static::$capsule::schema()->table('collections', function (Blueprint $table) {
                $table->json('submission');
            });
        }
    }
}
