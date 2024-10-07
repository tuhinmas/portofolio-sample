<?php

namespace Modules\Invoice\Database\factories;

use Illuminate\Support\Facades\DB;
use Modules\Invoice\Entities\CreditMemo;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreditMemoDetailFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Invoice\Entities\CreditMemoDetail::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $credit_memo = CreditMemo::factory()->create();
        $invoice = DB::table('invoices as i')
            ->join("credit_memos as cm", "cm.origin_id", "i.id")
            ->whereNull("i.deleted_at")
            ->whereNull("cm.deleted_at")
            ->where("cm.id", $credit_memo->id)
            ->first();
        $order_detail = SalesOrderDetail::factory()->create([
            "sales_order_id" => $invoice->sales_order_id,
        ]);

        return [
            "credit_memo_id" => $credit_memo->id,
            "product_id" => $order_detail->product_id,
            "package_name" => $order_detail->package_name,
            "quantity_on_package" => $order_detail->quantity_on_package,
            "quantity_order" => $order_detail->quantity,
            "quantity_return" => 3,
            "unit_price" => $order_detail->unit_price - (0 / $order_detail->quantity),
            "unit_price_return" => 12000.50,
            "total" => 36001.50,
        ];
    }
}
