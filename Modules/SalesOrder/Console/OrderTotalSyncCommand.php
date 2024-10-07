<?php

namespace Modules\SalesOrder\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Invoice\Entities\Invoice;

class OrderTotalSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'order:sync-total-amount';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'syn order discount and total. base data data is order item';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        protected Invoice $invoice
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $invoice = $this->invoice->query()
            ->with([
                "salesOrder" => function ($QQQ) {
                    return $QQQ->with([
                        "salesOrderDetail",
                    ]);
                },
            ])
            ->addSelect([
                "total_amount_according_item" => DB::table('sales_order_details as sod')
                    ->selectRaw("sum(sod.total)")
                    ->whereNull("sod.deleted_at")
                    ->whereNull("s.deleted_at")
                    ->join("sales_orders as s", "s.id", "sod.sales_order_id")
                    ->whereColumn('sod.sales_order_id', 'invoices.sales_order_id')
                    ->groupBy("sod.sales_order_id")
                    ->limit(1),
                "total_discount_according_item" => DB::table('sales_order_details as sod')
                    ->selectRaw("sum(sod.discount)")
                    ->whereNull("sod.deleted_at")
                    ->whereNull("s.deleted_at")
                    ->join("sales_orders as s", "s.id", "sod.sales_order_id")
                    ->whereColumn('sod.sales_order_id', 'invoices.sales_order_id')
                    ->groupBy("sod.sales_order_id")
                    ->limit(1),
                "total_order_minus_discount" => DB::table('sales_order_details as sod')
                    ->selectRaw("sum(sod.total) - sum(sod.discount)")
                    ->whereNull("sod.deleted_at")
                    ->whereNull("s.deleted_at")
                    ->join("sales_orders as s", "s.id", "sod.sales_order_id")
                    ->whereColumn('sod.sales_order_id', 'invoices.sales_order_id')
                    ->groupBy("sod.sales_order_id")
                    ->limit(1),
            ])
            ->whereHas("salesOrder", function($QQQ){
                return $QQQ->consideredOrder();
            })
            ->whereYear("created_at", "2023")
            ->where("change_locked", false)
            ->orderBy("created_at")
            ->lazy()
            ->each(function ($invoice) {
                
                if ($invoice->discount && $invoice->total_discount_according_item) {
                    if ($invoice->discount != $invoice->total_discount_according_item) {
                        dump([
                            "discount" => $invoice->discount,
                            "discount_item" => $invoice->total_discount_according_item,
                        ]);

                        $invoice->discount =  $invoice->total_discount_according_item;
                    }
                }

                if ($invoice->total && $invoice->total_order_minus_discount) {
                    if ($invoice->total != $invoice->total_order_minus_discount) {
                        dump([
                            "total" => $invoice->total,
                            "total_item" => $invoice->total_order_minus_discount,
                        ]);

                        $invoice->total =  $invoice->total_order_minus_discount;
                    }
                }

                if ($invoice->total && $invoice->salesOrder->total) {
                    if ($invoice->total != $invoice->salesOrder->total) {
                        dump([
                            "total_order" => $invoice->salesOrder->total,
                            "total_proforma" => $invoice->total,
                        ]);

                        $invoice->salesOrder->total = $invoice->total;
                        $invoice->salesOrder->save();
                    }
                }

                if ($invoice->isDirty("discount") || $invoice->isDirty("total")) {
                    $invoice->save();
                }
            })
            ->count();

        dd($invoice);
    }
}
