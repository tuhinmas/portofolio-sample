<?php

namespace Modules\DataAcuan\Database\factories;

use Modules\ProductGroup\Entities\ProductGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductMandatoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\DataAcuan\Entities\ProductMandatory::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $product_group = ProductGroup::factory()->create();
        return [
            "period_date" => now()->year,
            "product_group_id" => $product_group->id,
            "target" => 100,
        ];
    }
}
