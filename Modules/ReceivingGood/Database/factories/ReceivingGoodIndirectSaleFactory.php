<?php

namespace Modules\ReceivingGood\Database\factories;

use Modules\SalesOrder\Entities\SalesOrder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ReceivingGood\Entities\ReceivingGoodIndirectFile;
use Modules\ReceivingGood\Entities\ReceivingGoodIndirectSale;

class ReceivingGoodIndirectSaleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\ReceivingGood\Entities\ReceivingGoodIndirectSale::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $sales_order = SalesOrder::factory()->create([
            "type" => "2",
            "status" => "confirmed",
            "date" => now(),
        ]);

        return [
            "sales_order_id" => $sales_order->id,
            "status" => "2",
            "receiving_type" => 1,
            "date_received" => "2023-08-12",
            "shipping_number" => null,
            "note" => "test",
        ];
    }

    // public function configure()
    // {
    //     return $this->afterCreating(function (ReceivingGoodIndirectSale $receiving_good) {
    //         ReceivingGoodIndirectFile::factory()->create([
    //             "receiving_good_id" => $receiving_good->id,
    //         ]);
    //     });
    // }
}
