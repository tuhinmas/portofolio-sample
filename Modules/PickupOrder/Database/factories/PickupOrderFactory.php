<?php

namespace Modules\PickupOrder\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\DistributionChannel\Entities\DispatchOrder;

class PickupOrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\PickupOrder\Entities\PickupOrder::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $dispatch = DispatchOrder::factory()->create([
            "status" => "planned",
        ]);

        return [
            "warehouse_id" => $dispatch->id_warehouse,
            "driver_id" => $dispatch->id_armada,
            "pickup_number" => now()->year . $this->faker->word,
            "delivery_date" => $dispatch->date_delivery,
            "note" => null,
            "type_driver" => 'internal',
            "status" => "planned",
            "created_by" => null,
            "driver_name" => $dispatch->driver_name,
            "driver_phone_number" => $dispatch->driver_phone_number,
            "armada_identity_number" => $dispatch->armada_identity_number,
            "receipt_id" => "7"
        ];
    }
}
