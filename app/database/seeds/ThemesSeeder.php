<?php
namespace App\Database\Seeds;

use App\Models\Theme;
use App\Database\Factories\ThemeFactory;
use Illuminate\Database\Seeder;

class ThemesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @return void
     */
    public function run()
    {
        $themesRecords = file_get_contents(StoragePath('app/db/themes.json'));
        $themes = json_decode($themesRecords, true)['rows'];

        Theme::insert($themes);
    }
}
