<?php

use Leaf\Schema;
use Leaf\Database;
use Illuminate\Database\Schema\Blueprint;

/**
 * A report draws on several forms: `reports.forms` (JSON array of form ids,
 * in display order — the same shape as `collaborators` / `spaces`) replaces
 * the single `form_id`, which is backfilled into it and dropped.
 */
class CreateAlterReportsForms extends Database
{
    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        $schema = static::$capsule::schema();
        $db     = static::$capsule::connection();

        if (!$schema->hasColumn('reports', 'forms')) {
            $schema->table('reports', function (Blueprint $table) {
                $table->json('forms')->nullable()->after('filters');
                $table->json('svql_expressions')->nullable()->after('forms');
            });
        }

        if ($schema->hasColumn('reports', 'form_id')) {
            // carry the legacy link over, then drop the column and its key
            $db->statement('UPDATE reports SET forms = JSON_ARRAY(form_id) WHERE forms IS NULL AND form_id IS NOT NULL');

            if ($this->hasForeignKey($db, 'reports_form_id_foreign')) {
                $schema->table('reports', function (Blueprint $table) {
                    $table->dropForeign(['form_id']);
                });
            }

            $schema->table('reports', function (Blueprint $table) {
                $table->dropColumn('form_id');
            });
        }

        $db->statement('UPDATE reports SET forms = JSON_ARRAY() WHERE forms IS NULL');
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down()
    {
        $schema = static::$capsule::schema();
        $db     = static::$capsule::connection();

        if (!$schema->hasColumn('reports', 'form_id')) {
            $schema->table('reports', function (Blueprint $table) {
                $table->unsignedInteger('form_id')->nullable()->index()->after('filters');
            });
        }

        if ($schema->hasColumn('reports', 'forms')) {
            // only the first form fits the legacy single-form link
            $db->statement("UPDATE reports SET form_id = JSON_EXTRACT(forms, '$[0]') WHERE form_id IS NULL AND JSON_LENGTH(forms) > 0");

            $schema->table('reports', function (Blueprint $table) {
                $table->dropColumn('forms');
            });
        }
    }

    private function hasForeignKey($db, string $name): bool
    {
        return (bool) $db->selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$db->getDatabaseName(), 'reports', $name, 'FOREIGN KEY']
        );
    }
}
