<?php

use Leaf\Schema;
use Leaf\Database;
use Illuminate\Database\Schema\Blueprint;

class CreateApiActivities extends Database
{
    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        if (!static::$capsule::schema()->hasTable('api_activities')) :
            static::$capsule::schema()->create('api_activities', function (Blueprint $table) {
                $table->increments('id');
                $table->string('handler', 300);
                $table->string('origin', 100);
                $table->unsignedInteger('apikey_id');
                $table->integer('status');
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
        static::$capsule::schema()->dropIfExists('api_activities');
    }
}
