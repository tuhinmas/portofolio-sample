<?php

namespace Modules\SalesOrder\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SalesOrderOriginFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\SalesOrder\Entities\SalesOrderOrigin::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            "direct_id" => "",
            "parent_id" => "",
            "sales_order_detail_id" => "",
            "product_id" => "",
            "quantity_from_origin" => "",
            "direct_price" => "",
            "is_returned" => "",
        ];
    }
}

