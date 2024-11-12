<?php

use Leaf\Schema;
use Leaf\Database;
use Illuminate\Database\Schema\Blueprint;

class CreateTemplates extends Database
{
    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        if (!static::$capsule::schema()->hasTable('templates')) :
            static::$capsule::schema()->create('templates', function (Blueprint $table) {
                $table->increments('id');
                $table->string('title', 100);
                $table->text('description')->nullable();
                $table->text('content');
                $table->text('preview');
                $table->string('category', 50);
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            });
        endif;
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down()
    {
        static::$capsule::schema()->dropIfExists('templates');
    }
}
