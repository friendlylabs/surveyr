<?php

use Leaf\Schema;
use Leaf\Database;
use Illuminate\Database\Schema\Blueprint;

/*
CREATE TABLE `collections` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`form_id` INT(11) NOT NULL,
	`data` JSON NOT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`) USING BTREE
)
COLLATE='latin1_swedish_ci'
ENGINE=InnoDB
;
*/

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
