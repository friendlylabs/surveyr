<?php

use Leaf\Schema;
use Leaf\Database;
use Illuminate\Database\Schema\Blueprint;

/**
 * Flags replace the system-managed review types. `flag_groups` holds
 * user-created sets of `{key, label, color}` flags; a form opts in through
 * `forms.flag_group_id` and each submission keeps the chosen flag's key in
 * `collections.flag` (null = unflagged).
 *
 * `review_types`, `forms.reviews` and `collections.review` are dropped. The
 * old review states have no flag to map to, so existing rows start unflagged.
 */
class CreateFlagGroups extends Database
{
    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        $schema = static::$capsule::schema();
        $db     = static::$capsule::connection();

        if (!$schema->hasTable('flag_groups')) {
            $schema->create('flag_groups', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 50);
                $table->text('description')->nullable();
                $table->json('flags');
                $table->unsignedInteger('user_id');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (!$schema->hasColumn('forms', 'flag_group_id')) {
            $schema->table('forms', function (Blueprint $table) {
                $table->unsignedInteger('flag_group_id')->nullable()->after('spaces');
                $table->foreign('flag_group_id')->references('id')->on('flag_groups')->nullOnDelete();
            });
        }

        if ($schema->hasColumn('forms', 'reviews')) {
            $schema->table('forms', function (Blueprint $table) {
                $table->dropColumn('reviews');
            });
        }

        if ($schema->hasColumn('collections', 'review')) {
            $db->statement('ALTER TABLE collections CHANGE review flag CHAR(50) NULL DEFAULT NULL');
            $db->statement('UPDATE collections SET flag = NULL');
        }

        $schema->dropIfExists('review_types');
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down()
    {
        $schema = static::$capsule::schema();
        $db     = static::$capsule::connection();

        if (!$schema->hasTable('review_types')) {
            $schema->create('review_types', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 50);
                $table->json('properties');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            });
        }

        if ($schema->hasColumn('collections', 'flag')) {
            $db->statement("UPDATE collections SET flag = 'pending'");
            $db->statement("ALTER TABLE collections CHANGE flag review CHAR(50) NOT NULL DEFAULT 'pending'");
        }

        if (!$schema->hasColumn('forms', 'reviews')) {
            $schema->table('forms', function (Blueprint $table) {
                $table->json('reviews')->nullable();
            });
        }

        if ($schema->hasColumn('forms', 'flag_group_id')) {
            $schema->table('forms', function (Blueprint $table) {
                $table->dropForeign(['flag_group_id']);
                $table->dropColumn('flag_group_id');
            });
        }

        $schema->dropIfExists('flag_groups');
    }
}
