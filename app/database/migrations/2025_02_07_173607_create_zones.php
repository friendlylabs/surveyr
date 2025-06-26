<?php

use Leaf\Schema;
use Leaf\Database;
use Illuminate\Database\Schema\Blueprint;

class CreateZones extends Database
{
    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        if (!static::$capsule::schema()->hasTable('zones')) :
            static::$capsule::schema()->create('zones', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 50);
                $table->string('description', 50);
                $table->json('sheet')->nullable();
                $table->json('content')->nullable();
                $table->unsignedInteger('user_id');
                $table->json('editors')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

                // Indexes and Relationships
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        endif;
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down()
    {
        static::$capsule::schema()->dropIfExists('zones');
    }
}
