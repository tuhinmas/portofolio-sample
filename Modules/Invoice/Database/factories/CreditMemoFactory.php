<?php

namespace Modules\Invoice\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Invoice\Entities\Invoice;
use Modules\SalesOrder\Entities\SalesOrder;

class CreditMemoFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Invoice\Entities\CreditMemo::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $invoice = Invoice::factory()->create([
            "payment_status" => "settle",
        ]);
        $order = SalesOrder::findOrFail($invoice->sales_order_id);
        return [
            "personel_id" => $order->personel_id,
            "dealer_id" => $order->store_id,
            "origin_id" => $invoice->id,
            "destination_id" => $invoice->id,
            "date" => "2023-07-12",
            "status" => "accepted",
            "tax_invoice" => "010.000-24.00000001",
            "total" => 20000.50,
            "reason" => "produk tidak sesuai kebutuhan",
            "number" => "2024/KM-07/001",
            "number_order" => 1,
            "note" => null,
        ];
    }
}
