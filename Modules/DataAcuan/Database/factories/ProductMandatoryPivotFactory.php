<?php

namespace Modules\DataAcuan\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\DataAcuan\Entities\Product;
use Modules\DataAcuan\Entities\ProductMandatory;
use Modules\DataAcuan\Entities\ProductMandatoryPivot;

class ProductMandatoryPivotFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProductMandatoryPivot::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $product = Product::factory()->create([
            "metric_unit" => "Kg",
            "volume" => 1.0,
        ]);
        $product_mandatory = ProductMandatory::factory()->create();
        return [
            "product_mandatory_id" => $product_mandatory->id,
            "product_id" => $product->id,
            "period_date" => now()->year,
        ];
    }
}
