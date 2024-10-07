<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\SalesOrder\Entities\SalesOrder;

/**
 *
 */
trait FollowUpTrait
{
    public function salesOrderfollowUpDays($store_id, $model)
    {
        $last_order = SalesOrder::query()
            ->leftJoin("invoices as i", "i.sales_order_id", "sales_orders.id")
            ->leftJoin("dealers as d", "d.id", "sales_orders.store_id")
            ->leftJoin("sub_dealers as sd", "sd.id", "sales_orders.store_id")
            ->where("store_id", $store_id)
            ->whereIn("sales_orders.status", ["confirmed", "pending", "returned"])
            ->orderByRaw("if(type = 2, sales_orders.date, i.created_at) desc")
            ->select("sales_orders.*")
            ->first();

        $store = null;
        if ($model == "1") {
            $store = DB::table('dealers')->whereNull("deleted_at")
                ->where("id", $store_id)
                ->first();
        } else {
            $store = DB::table('sub_dealers')->whereNull("deleted_at")
                ->where("id", $store_id)
                ->first();
        }

        return ($last_order ? confirmation_time($last_order) : Carbon::parse($store->created_at))->diffInDays(now(), false);
    }
}
