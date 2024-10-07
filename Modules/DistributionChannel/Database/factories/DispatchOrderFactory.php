<?php

namespace Modules\DistributionChannel\Database\factories;

use Modules\Invoice\Entities\Invoice;
use Modules\DataAcuan\Entities\Driver;
use Modules\DataAcuan\Entities\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class DispatchOrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\DistributionChannel\Entities\DispatchOrder::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $proforma = Invoice::factory()->create();
        $aramada = Driver::factory()->create();
        $warehouse = Warehouse::factory()->create();
        return [
            "invoice_id" => $proforma->id,
            "id_armada" => $aramada->id,
            "id_warehouse" => $warehouse->id,
            "type_driver" => "external",
            "transportation_type" => "truck",
            "armada_identity_number" => "AB 123 CV",
            "driver_name" => "Budi",
            "driver_phone_number" => "13109108212",
            "date_delivery" => "2022-04-25",
            "dispatch_order_weight" => 1000,
            "is_active" => true,
        ];
    }
}
