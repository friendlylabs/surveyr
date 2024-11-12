<?php

use Leaf\Schema;
use Leaf\Database;
use Illuminate\Database\Schema\Blueprint;

class CreateSpaces extends Database
{
    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        if (!static::$capsule::schema()->hasTable('spaces')) :
            static::$capsule::schema()->create('spaces', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 50);
                $table->text('description')->nullable();
                $table->char('color', 9)->default('#000000');
                $table->unsignedInteger('user_id');
                $table->json('members')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

                // foreign keys
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
        static::$capsule::schema()->dropIfExists('spaces');
    }
}
