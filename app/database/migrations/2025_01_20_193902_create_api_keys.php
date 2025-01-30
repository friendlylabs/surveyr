<?php

use Leaf\Schema;
use Leaf\Database;
use Illuminate\Database\Schema\Blueprint;

class CreateApiKeys extends Database
{
    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        if (!static::$capsule::schema()->hasTable('api_keys')) :
            static::$capsule::schema()->create('api_keys', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 50);
                $table->unsignedInteger('user_id');
                $table->text('token');
                $table->text('secret');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->nullable();
            });
        endif;
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down()
    {
        static::$capsule::schema()->dropIfExists('api_keys');
    }
}
