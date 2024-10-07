<?php

namespace Modules\PickupOrder\Database\factories;

use Modules\PickupOrder\Entities\PickupOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class PickupOrderFileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\PickupOrder\Entities\PickupOrderFile::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $pickup_order = PickupOrder::factory()->create();
        return [
            "pickup_order_id" => $pickup_order->id,
            "caption" => "supir",
            "attachment" => "public/pickup-order/file/local/xxx.jpg",
        ];
    }
}
