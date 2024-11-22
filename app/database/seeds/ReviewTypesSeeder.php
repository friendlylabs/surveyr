<?php
namespace App\Database\Seeds;

use App\Models\ReviewType;
use App\Database\Factories\ReviewTypeFactory;
use Illuminate\Database\Seeder;

class ReviewTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @return void
     */
    public function run()
    {
        $review_types = array(
            array(
                "id" => 1,
                "name" => "default",
                "properties" => "[\"reviewed\", \"pending\"]",
                "created_at" => "2024-11-09 22:26:42",
                "updated_at" => "2024-11-10 00:36:13",
            ),
            array(
                "id" => 2,
                "name" => "basic",
                "properties" => "[\"approved\", \"rejected\", \"pending\"]",
                "created_at" => "2024-11-10 00:36:58",
                "updated_at" => "2024-11-10 00:36:58",
            ),
            array(
                "id" => 3,
                "name" => "advance",
                "properties" => "[\"passed\", \"whitelist\", \"rejected\", \"pending\"]",
                "created_at" => "2024-11-09 22:29:52",
                "updated_at" => "2024-11-10 00:35:59",
            ),
        );

        ReviewType::insert($review_types);
    }
}
