<?php

use Leaf\Schema;
use Leaf\Database;
use Illuminate\Database\Schema\Blueprint;

class CreateUsers extends Database
{
    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        if (!static::$capsule::schema()->hasTable("users")):
        	static::$capsule::schema()->create("users", function (Blueprint $table) {
                $table->increments('id');
                $table->string('username')->unique();
                $table->string('fullname');
                $table->string('email')->unique();
                $table->integer('email_verified')->default(0);
                $table->string('password');
                $table->string('remember_token')->nullable();
                $table->string('status')->default('active');
                $table->string('role')->default('user');
                $table->string('avatar')->default('/assets/images/users/avatar.jpg');
                $table->string('two_fa')->default(0);
                $table->string('notify_signin')->default(0);
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
        static::$capsule::schema()->dropIfExists('users');
    }
}
