<?php

namespace Modules\DistributionChannel\Actions;

use Illuminate\Support\Facades\DB;

class GetQuantityLoadedByProductAction
{
    public function __invoke(string $invoice_id, ?string $product_id = null, ?string $receiving_good_detail_id = null): int
    {
        $qty_loaded = DB::table('dispatch_order_detail as dod')
            ->join("discpatch_order as dis", "dod.id_dispatch_order", "=", "dis.id")
            ->leftJoin("delivery_orders as dor", function ($join) {
                $join->on("dor.dispatch_order_id", "=", "dis.id")
                    ->where("dor.status", "=", "send")
                    ->whereNull("dor.deleted_at");
            })
            ->leftJoin("receiving_goods as rg", function ($join) {
                $join->on("rg.delivery_order_id", "=", "dor.id")
                    ->where("rg.delivery_status", "=", "2")
                    ->whereNull("rg.deleted_at");
            })
            ->join("invoices as i", "i.id", "=", "dis.invoice_id")
            ->where("dis.is_active", true)
            ->whereNull("i.deleted_at")
            ->whereNull("dod.deleted_at")
            ->whereNull("dis.deleted_at")
            ->when($product_id, function ($QQQ) use ($product_id) {
                return $QQQ->where("dod.id_product", $product_id);
            })
            ->where("i.id", $invoice_id)
            ->when($receiving_good_detail_id, function ($QQQ) use ($receiving_good_detail_id) {
                return $QQQ->where("dod.id", "!=", $receiving_good_detail_id);
            })
            ->select("dod.*", "dor.id as delivery_order_id", "rg.id as receiving_good_id")
            ->get();

        /* qty received */
        $qty_received = DB::table('receiving_good_details as rgd')
            ->join("receiving_goods as rg", "rg.id", "rgd.receiving_good_id")
            ->whereNull("rgd.deleted_at")
            ->whereNull("rg.deleted_at")
            ->where("rgd.status", "delivered")
            ->where("rg.delivery_status", "2")
            ->when($product_id, function ($QQQ) use ($product_id) {
                return $QQQ->where("rgd.product_id", $product_id);
            })
            ->whereIn("rg.id", $qty_loaded->filter(fn($dispatch) => $dispatch->receiving_good_id)->pluck("receiving_good_id")->toArray())
            ->sum("quantity");

        $qty_loaded = $qty_loaded
            ->reject(fn($dispatch) => $dispatch->receiving_good_id)
            ->sum(function ($dispatch) {
                if ($dispatch->delivery_order_id) {
                    return $dispatch->quantity_unit;
                }
                return $dispatch->planned_quantity_unit;
            }) + $qty_received;

        return $qty_loaded;
    }
}
