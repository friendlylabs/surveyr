<?php

use Leaf\Schema;
use Leaf\Database;
use Illuminate\Database\Schema\Blueprint;

class CreateCollectionPayloads extends Database
{
    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        if (!static::$capsule::schema()->hasTable('collection_payloads')) :
            static::$capsule::schema()->create('collection_payloads', function (Blueprint $table) {
                $table->unsignedInteger('collection_id')->primary();
                $table->json('submission');
                
                $table->foreign('collection_id')->references('id')->on('collections')->onDelete('cascade');
            });
        endif;
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down()
    {
        static::$capsule::schema()->dropIfExists('collection_payloads');
    }
}
