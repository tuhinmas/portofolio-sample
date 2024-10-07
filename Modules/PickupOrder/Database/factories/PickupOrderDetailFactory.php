<?php

namespace Modules\PickupOrder\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\PickupOrder\Entities\PickupOrderDispatch;
use Modules\DistributionChannel\Entities\DispatchOrderDetail;

class PickupOrderDetailFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\PickupOrder\Entities\PickupOrderDetail::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $pickup_order = PickupOrderDispatch::factory()->create();
        $dispatch_detail = DispatchOrderDetail::factory()->create([
            "id_dispatch_order" => $pickup_order->dispatch_id,
        ]);
        return [
            "pickup_order_id" => $pickup_order->pickup_order_id,
            "pickup_type" => "load",
            "product_id" => $dispatch_detail->id_product,
            "product_name" => "Tomat Dus",
            "type" => "Dus",
            "quantity_unit_load" => $dispatch_detail->planned_quantity_unit,
            "quantity_actual_load" => $dispatch_detail->quantity_unit,
            "unit" => "Buah",
            "weight" => 5.00,
            "total_weight" => $dispatch_detail->package_weight,
            "estimate_weight" => $dispatch_detail->package_weight,
            "is_loaded" => 0,
            "detail_type" => "dispatch_order",
        ];
    }
}
