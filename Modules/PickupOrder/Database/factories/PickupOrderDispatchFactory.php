<?php

namespace Modules\PickupOrder\Database\factories;

use Modules\PickupOrder\Entities\PickupOrder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\DistributionChannel\Entities\DispatchOrder;

class PickupOrderDispatchFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\PickupOrder\Entities\PickupOrderDispatch::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $pickup_order = PickupOrder::factory()->create();
        $dispatch = DispatchOrder::factory()->create([
            "status" => "planned",
        ]);

        return [
            "pickup_order_id" => $pickup_order->id,
            "dispatch_id" => $dispatch->id,
            "dispatch_type" => "dispatch_order"
        ];
    }
}
