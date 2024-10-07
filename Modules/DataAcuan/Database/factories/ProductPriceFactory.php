<?php

namespace Modules\DataAcuan\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\DataAcuan\Entities\AgencyLevel;
use Modules\DataAcuan\Entities\Product;

class ProductPriceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\DataAcuan\Entities\Price::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $agency_level_id = AgencyLevel::firstOrCreate([
            "name" => "R3",
        ], [
            "agency" => "1",
        ]);
        $product = Product::factory()->create();
        return [
            "product_id" => $product->id,
            "agency_level_id" => $agency_level_id->id,
            "het" => 15000,
            "price" => 10000,
            "minimum_order" => 1,
            "valid_from" => now(),
        ];
    }
}
