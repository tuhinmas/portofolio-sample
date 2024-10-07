<?php

namespace Modules\PickupOrder\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\DataAcuan\Entities\Position;
use Modules\Personel\Entities\Personel;
use Modules\PickupOrder\Entities\PickupOrderDispatch;

class PickupLoadHistoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\PickupOrder\Entities\PickupLoadHistory::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $pickup_order = PickupOrderDispatch::factory()->create();
        $support_position = Position::firstOrCreate([
            "name" => "Support",
        ]);
        $support = Personel::factory()->create([
            "position_id" => $support_position->id,
        ]);

        return [
            "pickup_order_id" => $pickup_order->pickup_order_id,
            "dispatch_id" => $pickup_order->dispatch_id,
            "dispatch_type" => "dispatch_order",
            "dispatch" => null,
            "status" => "created",
            "notes" => null,
            "created_by" => $support->id,
        ];
    }
}
