<?php

use Leaf\Schema;
use Leaf\Database;
use Illuminate\Database\Schema\Blueprint;

/**
 * Saved submission filters ("views") of a form. `filters` has the shape
 * Collection::formCollections() takes; anyone with access to the form may use
 * and manage them, so there is no owner column.
 */
class CreateCollectionFilters extends Database
{
    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        if (!static::$capsule::schema()->hasTable('collection_filters')) :
            static::$capsule::schema()->create('collection_filters', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 120);
                $table->string('description', 300)->nullable();
                $table->unsignedInteger('form_id')->index();
                $table->json('filters');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

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
        static::$capsule::schema()->dropIfExists('collection_filters');
    }
}
