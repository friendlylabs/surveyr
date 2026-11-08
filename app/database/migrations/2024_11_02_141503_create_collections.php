<?php

use Leaf\Schema;
use Leaf\Database;
use Illuminate\Database\Schema\Blueprint;

class CreateCollections extends Database
{
    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        if (!static::$capsule::schema()->hasTable('collections')) :
            static::$capsule::schema()->create('collections', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('form_id');
                $table->json('submission');
                $table->char('review', 50)->default('pending');
                $table->boolean('is_archived')->default(false);
                $table->boolean('is_optimized')->default(false);
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

                // foreign keys
                $table->foreign('form_id')->references('id')->on('forms')->onDelete('cascade');
            });
        endif;
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down()
    {
        static::$capsule::schema()->dropIfExists('collections');
    }
}
