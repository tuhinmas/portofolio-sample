<?php

namespace Modules\Personel\Actions\Point;

use Modules\SalesOrder\Entities\LogWorkerPointMarketing;
use Modules\SalesOrder\Entities\LogWorkerPointMarketingActive;

class GetMarketingPointActiveAction
{
    public function __invoke($sales_orders, $year): int
    {

        /* maximum payment days */
        $maximum_settle_days = maximum_settle_days($year);

        $point_marketing_active = $sales_orders
            ->filter(function ($order) use ($maximum_settle_days) {

                /* check order considered active or not */
                $is_order_considerd_active = is_considered_order_as_active_marketing_point($order, $maximum_settle_days);

                if ($is_order_considerd_active) {
                    return $order;
                }
                return false;
            })

            /* logging */
            ->each(function ($order) {

                LogWorkerPointMarketingActive::firstOrCreate([
                    "sales_order_id" => $order->id,
                ]);

                LogWorkerPointMarketing::updateOrCreate([
                    "sales_order_id" => $order->id,
                ], [
                    "is_count" => "1",
                    "is_active" => "1",
                ]);
            })
            ->pluck("salesOrderDetail")
            ->flatten()
            ->sum("marketing_point");

        return $point_marketing_active;
    }
}
