<?php

use Leaf\Schema;
use Leaf\Database;
use Illuminate\Database\Schema\Blueprint;

class CreateForms extends Database
{
    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        if (!static::$capsule::schema()->hasTable('forms')) :
            static::$capsule::schema()->create('forms', function (Blueprint $table) {
                $table->increments('id');
                $table->string('title', 50);
                $table->text('description')->nullable();
                $table->string('slug', 50);
                $table->json('content');
                $table->json('questions')->nullable();
                $table->unsignedInteger('user_id');
                $table->json('collaborators')->nullable();
                $table->json('spaces')->nullable();
                $table->tinyInteger('is_locked')->default(0);
                $table->tinyInteger('current_editor')->default(0);
                $table->tinyInteger('is_indefinite')->default(0);
                $table->json('reviews')->nullable();
                $table->text('webhook_url')->nullable();
                $table->text('access_code')->nullable();
                $table->char('theme')->default('Solid');
                $table->timestamp('start_date')->useCurrent();
                $table->timestamp('end_date')->nullable();
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
        static::$capsule::schema()->dropIfExists('forms');
    }
}
