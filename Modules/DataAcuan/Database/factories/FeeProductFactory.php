<?php

namespace Modules\DataAcuan\Database\factories;

use Modules\DataAcuan\Entities\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeeProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\DataAcuan\Entities\Fee::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $product = Product::factory()->create();

        return [
            "year" => now()->year,
            "quartal" => now()->quarter,
            "type" => "1",
            "product_id" => $product->id,
            "quantity" => 1,
            "fee" => "1000",
        ];
    }
}
