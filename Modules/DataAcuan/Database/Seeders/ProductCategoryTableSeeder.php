<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\ProductCategory;

class ProductCategoryTableSeeder extends Seeder
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
                "name" => "a"
            ],
            [
                "name" => 'b'
            ],
            [
                "name" => "special"
            ],
        ];

        foreach ($categories as $category) {
            ProductCategory::firstOrCreate($category);
        }
    }
}
