<?php

namespace Modules\DistributionChannel\Actions;

use Illuminate\Support\Facades\DB;

class GetQuantityOrderByProductAndDispatchAction
{
    /**
     * quantity order by product and dispatch order
     *
     * @param string $dispatch_order_id
     * @param string $product_id
     * @return void
     */
    public function __invoke(string $dispatch_order_id, ?string $product_id = null)
    {
        return DB::table('sales_order_details as sod')
            ->join("sales_orders as s", "s.id", "sod.sales_order_id")
            ->join("invoices as i", "i.sales_order_id", "s.id")
            ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
            ->whereNull("sod.deleted_at")
            ->whereNull("s.deleted_at")
            ->whereNull("i.deleted_at")
            ->whereNull("dis.deleted_at")
            ->when($product_id, function ($QQQ) use ($product_id) {
                return $QQQ->where("sod.product_id", $product_id);
            })
            ->where("dis.id", $dispatch_order_id)
            ->select("sod.*", "i.id as invoice_id")
            ->first();
    }
}
