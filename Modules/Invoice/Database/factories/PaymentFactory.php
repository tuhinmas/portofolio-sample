<?php

namespace Modules\Invoice\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Authentication\Entities\User;
use Modules\Invoice\Entities\Invoice;
use Modules\SalesOrder\Entities\SalesOrder;

class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Invoice\Entities\Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $sales_order = SalesOrder::factory()->create([
            "type" => 1,
            "status" => "confirmed",
        ]);

        $invoice = Invoice::factory()->create([
            "sales_order_id" => $sales_order->id,
        ]);

        $user = User::where("name", "support")->first();

        return [
            "invoice_id" => $invoice->id,
            "payment_date" => now()->format("Y-m-d"),
            "nominal" => 10000,
            "remaining_payment" => 1000,
            "user_id" => $user->id,
            "reference_number" => $this->faker->word,
            "remaining_payment" => ($invoice->total + $invoice->ppn) - 10000,
        ];
    }
}
