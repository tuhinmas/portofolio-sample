<?php

namespace Modules\DistributionChannel\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\DistributionChannel\Entities\DeliveryOrder;

class DeliveryOrderNumberFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\DistributionChannel\Entities\DeliveryOrderNumber::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $delivery = DeliveryOrder::factory()->create();
        return [
            "dispatch_order_id" => $delivery->dispatch_order_id,
            "dispatch_promotion_id" => $delivery->dispatch_promotion_id,
            "delivery_order_id" => $delivery->id,
            "delivery_order_number" => $delivery->delivery_order_number,
        ];
    }
}
