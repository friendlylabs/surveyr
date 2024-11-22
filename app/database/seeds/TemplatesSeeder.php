<?php
namespace App\Database\Seeds;

use App\Models\Template;
use App\Database\Factories\TemplateFactory;
use Illuminate\Database\Seeder;

class TemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @return void
     */
    public function run()
    {
        $templatesRecords = file_get_contents(StoragePath('app/db/templates.json'));
        $templates = json_decode($templatesRecords, true)['rows'];

        Template::insert($templates);
    }
}
