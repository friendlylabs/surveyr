<?php

use Leaf\Schema;
use Leaf\Database;
use Illuminate\Database\Schema\Blueprint;

/*
CREATE TABLE `reports` (
	`id` INT(10) NOT NULL AUTO_INCREMENT,
	`title` VARCHAR(160) NOT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`description` VARCHAR(300) NULL DEFAULT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`content` JSON NULL DEFAULT NULL,
	`filters` LONGTEXT NULL DEFAULT NULL COLLATE 'utf8mb4_0900_ai_ci',
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`) USING BTREE
)
ENGINE=InnoDB
;
*/

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
                $table->longText('filters')->nullable();
                $table->unsignedInteger('form_id')->index();
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
        static::$capsule::schema()->dropIfExists('reports');
    }
}
