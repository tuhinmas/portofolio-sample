<?php

namespace Modules\DistributionChannel\Database\factories;

use Modules\SalesOrder\Entities\SalesOrderDetail;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\DistributionChannel\Entities\DispatchOrder;

class DispatchOrderDetailFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\DistributionChannel\Entities\DispatchOrderDetail::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {

        $dispatch_order = DispatchOrder::factory()->create();
        $product = SalesOrderDetail::factory()->create();
        return [
            "id_dispatch_order" => $dispatch_order->id,
            "id_product" => $product->product_id,
            "quantity_packet_to_send" => 1,
            "package_weight" => 10,
            "quantity_unit" => 20,
            "date_received" => now()->addDays(2),
            "planned_package_to_send" => 1,
            "planned_package_weight" => 10,
            "planned_quantity_unit" => 20,
        ];
    }
}
