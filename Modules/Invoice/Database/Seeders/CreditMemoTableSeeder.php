<?php

namespace Modules\Invoice\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Invoice\Entities\CreditMemoDetail;
use Modules\Invoice\Entities\Invoice;

class CreditMemoTableSeeder extends Seeder
{
    public function __construct(
        protected CreditMemoDetail $credit_memo_detail,
        protected Invoice $invoice,
    ) {}

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $support = DB::table('users')
            ->where("email", "support@mail.com")
            ->first();

        $this->invoice->query()
            ->with([
                "salesOrder.salesOrderDetail",
                "lastPayment",
            ])
            ->whereHas("salesOrder", function ($QQQ) {
                return $QQQ->whereHas("salesOrderDetail", function ($QQQ) {
                    return $QQQ->where("returned_quantity", ">", 0);
                });
            })
            // ->whereIn("id", [
            //     "f3fb0912-6159-478d-876e-0fd9ec9b9597",
            //     "edc87791-1a54-4f86-b9d7-b8c9637a4548",
            //     "aefe11ad-326a-4b74-824a-cbee62889835",
            // ])
            ->whereDoesntHave("creditMemos")
            ->orderBy("created_at")
            ->lazyById(20, $column = 'id')

        /**
         * assuming there is no discount in this direct sales, if exist we need to discuse it letter
         */
            ->filter(function ($invoice) {
                return $invoice->salesOrder->salesOrderDetail->reduce(fn($item, $product) => $item + $product->discount) == 0;
            })

        /**
         * make sure invoice total is match to sub total
         */
            ->filter(function ($invoice) {
                return $invoice->total == $invoice->salesOrder->salesOrderDetail->reduce(fn($item, $product) => $item + ($product->quantity * $product->unit_price));
            })

        /**
         * make sure total order detail is match to quantity return, of course disoucnt
         * considered here, but in the first line we commit to assume that no
         * discount here
         */
            ->filter(function ($invoice) {

                return $invoice->salesOrder->salesOrderDetail
                    ->filter(function ($detail) {
                        $new_total = (
                            ($detail->quantity * $detail->unit_price) - $detail->discount)
                             - (
                            ($detail->returned_quantity * $detail->unit_price)
                             - ($detail->discount > 0 ? $detail->discount / $detail->quantity * $detail->returned_quantity : 0)
                        );
                        return $detail->total != $new_total;
                    })
                    ->count() == 0;
            })

        /**
         * make a memmo and payment memo for proforma it self
         */
            ->each(function ($invoice) use ($support) {
                dump([
                    "invoice" => $invoice->invoice,
                    "payment_status" => $invoice->payment_status,
                    "invoice_total" => $invoice->total,
                    "product_sub_total" => $invoice->salesOrder->salesOrderDetail->reduce(fn($item, $product) => $item + ($product->quantity * $product->unit_price)),
                    "retrun_total" => $invoice->salesOrder->salesOrderDetail->reduce(fn($item, $product) => $item + ($product->returned_quantity * $product->unit_price)),
                ]);

                $credit_memo = self::createMemoforItSelf($invoice, $support);
                self::createPaymentMemo($invoice, $credit_memo, $support);
            });
    }

    public function createMemoforItSelf($invoice, $user)
    {
        $nominal_return = $invoice
            ->salesOrder
            ->salesOrderDetail
            ->filter(fn($order_detail) => $order_detail->returned_quantity > 0)
            ->reduce(fn($total, $order_detail) => $total + ($order_detail->unit_price * $order_detail->returned_quantity));

        $number = self::numberGenerator();

        $credit_memo = $invoice->creditMemos()->create([
            "personel_id" => $user?->personel_id,
            "dealer_id" => $invoice->salesOrder->store_id,
            "origin_id" => $invoice->id,
            "destination_id" => $invoice->id,
            "date" => $invoice->created_at,
            "status" => "accepted",
            "total" => $nominal_return,
            "reason" => "kredit memo seeding",
            "number" => $number["number"],
            "number_order" => $number["number_order"],
            "note" => "kredit memo seeding",
        ]);

        $invoice
            ->salesOrder
            ->salesOrderDetail
            ->filter(fn($order_detail) => $order_detail->returned_quantity > 0)
            ->each(function ($order_detail) use ($credit_memo) {
                $this->credit_memo_detail->create([
                    "credit_memo_id" => $credit_memo->id,
                    "product_id" => $order_detail->product_id,
                    "package_name" => $order_detail->package_name,
                    "quantity_on_package" => $order_detail,
                    "quantity_order" => $order_detail->quantity,
                    "quantity_return" => $order_detail->returned_quantity,
                    "unit_price" => $order_detail->unit_price,
                    "unit_price_return" => $order_detail->unit_price,
                    "total" => $order_detail->unit_price * $order_detail->returned_quantity,
                ]);
            });

        /* Make history of memo */
        $credit_memo->creditMemoHistories()->create([
            "personel_id" => $user?->personel_id,
            "credit_memo_id" => $credit_memo->id,
            "status" => $credit_memo->status,
        ]);

        return $credit_memo;
    }

    public function createPaymentMemo($invoice, $credit_memo, $user)
    {
        $payment_date = $invoice->created_at->addDays(1);
        $remaining_payment = 0;
        switch (true) {
            case $invoice->last_payment != "-":
                $payment_date = Carbon::parse($invoice->last_payment)->addDays(1);
                $remaining_payment = $invoice->lastPayment()->first()->remaining_payment - $credit_memo->total;
                break;

            default:
                $remaining_payment = $invoice->total + $invoice->ppn;
                break;
        }

        $invoice->payment()->create([
            "nominal" => $credit_memo->total,
            "reference_number" => $credit_memo->number,
            "remaining_payment" => $remaining_payment,
            "user_id" => $user->id,
            "payment_date" => $payment_date,
            "is_credit_memo" => true,
            "credit_memo_id" => $credit_memo->id,
            "memo_status" => "accepted",
        ]);
    }

    public static function numberGenerator()
    {
        /**
         * 2024/KM-06/001
         */
        $last_memo = DB::table('credit_memos')
            ->whereNull("deleted_at")
            ->whereYear("created_at", now()->year)
            ->orderByDesc("number_order")
            ->first();

        $number = $last_memo ? $last_memo->number_order + 1 : 1;
        return [
            "number" => now()->year . "/KM-" . now()->format('m') . "/" . Str::padLeft($number, 3, '0'),
            "number_order" => $number,
        ];
    }
}
