<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\PlantCategory;

class PlantCategoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $categories = [
            [
                "name" => "tanaman pangan"
            ],
            [
                "name" => "tanaman hias"
            ],
            [
                "name" => "tanaman buah"
            ],
        ];

        foreach ($categories as $category) {
            PlantCategory::firstOrCreate($category);
        }
    }
}
