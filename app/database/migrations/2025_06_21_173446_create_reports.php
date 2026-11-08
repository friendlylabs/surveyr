<?php

use Leaf\Schema;
use Leaf\Database;
use Illuminate\Database\Schema\Blueprint;

class CreateReports extends Database
{
    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        if (!static::$capsule::schema()->hasTable('reports')) :
            static::$capsule::schema()->create('reports', function (Blueprint $table) {
                $table->increments('id');
                $table->string('title', 160);
                $table->string('description', 300)->nullable();
                $table->json('content')->nullable();
                $table->json('draft')->nullable();
                $table->json('filters')->nullable();
                $table->unsignedInteger('form_id')->index();
                $table->unsignedInteger('user_id')->index();
                $table->json('collaborators')->nullable();
                $table->integer('is_public')->default(0); // 0: private, 1: public
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

                # Indexes and Relationships
                $table->foreign('form_id')->references('id')->on('forms')->onDelete('cascade');
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
        static::$capsule::schema()->dropIfExists('reports');
    }
}
