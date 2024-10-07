<?php

namespace Modules\Personel\Traits;

use Carbon\Carbon;
use Spatie\Activitylog\Contracts\Activity;

/**
 * order return marker
 */
trait OrderReturnTrait
{
    public function orderReturnMarker($sales_order)
    {
        /*
        |----------------------------------------------------------------------------------
        | marking sales order origin as non counted fee if it's direct sale
        | was returned, or indirect sale return then only, derivative order
        | that not count fee is only in product returned, date is from
        | confirmation_time
        |----------------------------------------------------------------------------------
         */

        $year_of_return = confirmation_time($sales_order)->format("Y");
        $quarter_of_return = confirmation_time($sales_order)->quarter;

        $non_counted_fee_marketing = collect();
        if ($year_of_return && $quarter_of_return) {

            /* all dealer / sub dealer order in quartal will not counted as fee*/
            $order_quartal = $this->sales_order->query()
                ->where("store_id", $sales_order->store_id)
                ->where("personel_id", $sales_order->personel_id)
                ->consideredOrderForReturn()
                ->where(function ($QQQ) use ($year_of_return, $quarter_of_return) {
                    return $QQQ
                        ->quartalOrder($year_of_return, $quarter_of_return)
                        ->orwhere(function ($QQQ) use ($year_of_return, $quarter_of_return) {
                            return $QQQ->unconfirmedOrUnSubmitedOrderQuartal($year_of_return, $quarter_of_return);
                        });
                })
                ->get()
                ->each(function ($order) use ($sales_order) {
                    $order->afftected_by_return = $sales_order->id;
                    $order->save();
                });

            $non_counted_fee_marketing->push($order_quartal->pluck("id"));

            /*
            | Derivative order from this order which contain returned product
            | also will marked as non counted fee marketing (reguler/target)
            | inside returned quartal order
            |
             */
            /* check returned product */
            $returned_product = collect($sales_order->salesOrderDetail)
                ->whereNotNull("returned_quantity")
                ->each(function ($order_detail) {
                    $order_detail->marketing_point = 0;
                    $order_detail->save();
                })
                ->pluck("product_id");

            /* origin store was return */
            $sales_order_origins = $this->sales_order_origin
                ->with([
                    "salesOrder",
                    "salesOrderDetail",
                ])
                ->where(function ($QQQ) use ($sales_order) {
                    return $QQQ
                        ->where("direct_id", $sales_order->id)
                        ->orWhere("parent_id", $sales_order->id);
                })
                ->whereIn("product_id", $returned_product->toArray())
                ->whereYear("confirmed_at", $year_of_return)
                ->whereRaw("quarter(confirmed_at) = ?", $quarter_of_return)
                ->orderby("direct_id")
                ->get();

            if ($sales_order_origins->count() > 0) {
                $non_counted_fee_marketing->push($sales_order_origins->pluck("parent_id"));
                $non_counted_fee_marketing->push($sales_order_origins->pluck("sales_order_id"));

                /* origin set to non coiunted fee */
                $sales_order_origins
                    ->each(function ($origin) use ($sales_order) {
                        if ($origin->sales_order_id == $sales_order->id) {
                            $origin->update([
                                "is_returned" => 1,
                            ]);
                        }

                        $origin->update([
                            "is_fee_counted" => 0,
                        ]);

                        $origin->salesOrderDetail->update([
                            "marketing_fee_reguler" => 0,
                            "marketing_point" => 0,
                        ]);
                    })
                    ->groupBy("sales_order_id")
                    ->each(function ($origin_per_order, $sales_order_id) use ($sales_order) {
                        $origin_per_order->first()->salesOrder->afftected_by_return = $sales_order->id;
                        $origin_per_order->first()->salesOrder->save();
                    });

                $non_counted_fee_marketing->push($sales_order_origins->pluck("sales_order_id"));
            }

            /**
             * fee sharing marking as non counted
             * fee marketing
             */
            $fee_sharing_origin_update = $this->fee_sharing_origin->query()
                ->whereIn("sales_order_id", $non_counted_fee_marketing->flatten()->toArray())
                ->update([
                    "is_returned" => "1",
                ]);

            $log_fee_delete = $this->log_marketing_fee_counter->query()
                ->whereIn("sales_order_id", $non_counted_fee_marketing->flatten()->toArray())
                ->delete();

            /**
             * fee target sharing marking as non counted fee
             * marketing
             */
            $fee_target_sharing_origin = $this->fee_target_sharing_origin->query()
                ->whereIn("sales_order_id", $non_counted_fee_marketing->flatten()->toArray())
                ->update([
                    "is_returned" => "1",
                ]);

            return "order marked";
        }
    }
}
