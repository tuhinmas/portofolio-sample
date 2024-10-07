<?php

namespace Modules\ReceivingGood\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\DistributionChannel\Entities\DeliveryOrder;

class ReceivingGoodFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\ReceivingGood\Entities\ReceivingGood::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $delivery_order = DeliveryOrder::factory()->create();
        return [
            "delivery_order_id" => $delivery_order->id,
            "date_received" => now()->format("Y-m-d"),
            "delivery_status" => "2", // received
            "note" => "factory",
        ];
    }
}
