<?php

namespace Modules\DataAcuan\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\DataAcuan\Entities\Price;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Product;
use Modules\DataAcuan\Entities\AgencyLevel;

class PriceTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $product = Product::factory()->create();
        $agency_level = AgencyLevel::factory()->create();
        Model::unguard();
        $prices = [
            [
                'product_id' => $product->id,
                'agency_level_id' => $agency_level->id,
                'het' => "27000",
                'price' => "25000",
                'minimum_order' => 50,
            ],
            [
                'product_id' => $product->id,
                'agency_level_id' => $agency_level->id,
                'het' => "270000",
                'price' => "250000",
                'minimum_order' => 100,
            ]
        ];
        foreach($prices as $price){
            Price::create($price);
        }
    }
}
