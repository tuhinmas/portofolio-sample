<?php

namespace Modules\DistributionChannel\Actions;

use Illuminate\Support\Facades\DB;
use Modules\DistributionChannel\Entities\DeliveryOrder;

class GetProductDispatchAction
{
    public function __invoke(string $delivery_order_id)
    {
        $delivery_order = DeliveryOrder::findOrFail($delivery_order_id);
        
        return match (true) {
            (bool) $delivery_order->dispatch_promotion_id => DB::table('dispatch_promotion_details as dpd')
                ->join("dispatch_promotions as dp", "dp.id", "dpd.dispatch_promotion_id")
                ->join("delivery_orders as deo", "dp.id", "deo.dispatch_promotion_id")
                ->join("promotion_good_requests as pgr", "pgr.id", "dp.promotion_good_request_id")
                ->join("promotion_goods as pg", "pgr.id", "pg.promotion_good_request_id")
                ->leftJoin("products as p", "p.id", "pg.product_id")
                ->whereNull("dpd.deleted_at")
                ->whereNull("dp.deleted_at")
                ->whereNull("deo.deleted_at")
                ->whereNull("pgr.deleted_at")
                ->whereNull("pg.deleted_at")
                ->whereColumn("pg.id", "dpd.promotion_good_id")
                ->where("dp.is_active", true)
                ->where("deo.id", $delivery_order_id)
                ->where("deo.status", "send")
                ->selectRaw(
                    "p.id as product_id,
                    if(pg.product_id is not null, p.name, pg.name) as product_name,
                    if(pg.product_id is not null, p.unit, pg.unit) as product_unit,
                    if(pg.product_id is not null, p.size, pg.size) as product_size,
                    if(pg.product_id is not null, p.type, pg.type) as product_type,
                    if(dpd.quantity_unit > 0, dpd.quantity_unit, dpd.planned_quantity_unit) as sent_unit_quantity,
                    if(dpd.quantity_packet_to_send > 0, dpd.quantity_packet_to_send, dpd.planned_package_to_send) as sent_package_quantity,
                    null as sent_package_name,
                    pg.id as promotion_good_id"
                )
                ->orderBy("p.name")
                ->get(),

            default => DB::table('dispatch_order_detail as dod')
                ->join("discpatch_order as dis", "dis.id", "dod.id_dispatch_order")
                ->join("delivery_orders as deo", "dis.id", "deo.dispatch_order_id")
                ->join("invoices as i", "i.id", "dis.invoice_id")
                ->join("sales_orders as s", "s.id", "i.sales_order_id")
                ->join("sales_order_details as sod", "s.id", "sod.sales_order_id")
                ->join("products as p", "p.id", "dod.id_product")
                ->whereNull("dod.deleted_at")
                ->whereNull("dis.deleted_at")
                ->whereNull("deo.deleted_at")
                ->whereNull("i.deleted_at")
                ->whereNull("s.deleted_at")
                ->whereNull("sod.deleted_at")
                ->whereColumn("sod.product_id", "dod.id_product")
                ->where("dis.is_active", true)
                ->where("deo.id", $delivery_order_id)
                ->where("deo.status", "send")
                ->select(
                    "p.id as product_id",
                    "p.name as product_name",
                    "p.unit as product_unit",
                    "p.size as product_size",
                    "p.type as product_type",
                    DB::raw("if(dod.quantity_unit > 0, dod.quantity_unit, dod.planned_quantity_unit) as sent_unit_quantity"),
                    DB::raw("if(dod.quantity_packet_to_send > 0, dod.quantity_packet_to_send, dod.planned_package_to_send) as sent_package_quantity"),
                    "sod.package_name as sent_package_name",
                )
                ->orderBy("p.name")
                ->get()
        };
    }
}
