<?php

use Leaf\Schema;
use Leaf\Database;
use Illuminate\Database\Schema\Blueprint;

class CreateUserRoles extends Database
{
    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        if (!static::$capsule::schema()->hasTable('user_roles')) :
            static::$capsule::schema()->create('user_roles', function (Blueprint $table) {
                $table->increments('id');
                $table->char('name', 50)->unique();
                $table->string('description')->nullable();
                $table->integer('is_admin')->default(0);
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
        static::$capsule::schema()->dropIfExists('user_roles');
    }
}
