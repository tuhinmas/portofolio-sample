<?php

namespace Modules\Invoice\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Authentication\Entities\User;
use Modules\SalesOrder\Entities\SalesOrder;

class InvoiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Invoice\Entities\Invoice::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $sales_order = SalesOrder::factory()->create([
            "type" => 1,
            "status" => "confirmed"
        ]);

        $user = User::where("name", "support")->first();
        return [
            "sales_order_id" => $sales_order->id,
            "sub_total" => 30480000.00,
            "discount" => 762000.00,
            "total" => 29718000.00,
            "ppn" => 3268980,
            "invoice" => rand(1, 1000000),
            "payment_status" => "unpaid",
            "user_id" => $user->id,
        ];
    }
}
