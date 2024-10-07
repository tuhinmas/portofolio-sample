<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Product;
use Modules\DataAcuan\Entities\ProductCategory;

class ProducCategoryUpdateTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $category_a = ProductCategory::where("name", "a")->first();
        $category_b = ProductCategory::where("name", "b")->first();
        $category_s = ProductCategory::where("name", "special")->first();

        Product::where("category", "A")->update([
            "category" => $category_a->id
        ]);

        Product::where("category", "B")->update([
            "category" => $category_b->id
        ]);
        
        Product::where("category", "S")->update([
            "category" => $category_s->id
        ]);
    }
}
