<?php

namespace Modules\Personel\Actions\Point;

use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\SalesOrder\Entities\LogWorkerSalesPoint;
use Modules\Personel\Actions\Point\GetPointProductByYearPerProductAction;

class CalculateMarketingPointPerProductAction
{
    public function __invoke($sales_order)
    {
        /*
        |-----------------------------------------------------------------
        | return order or affected from retrun still get point
        | and calculate for point total only
        |-----------------------------------------------------
        |
         */

        /**
         * current point products
         */
        $point_product_action = new GetPointProductByYearPerProductAction();

        SalesOrderDetail::query()
            ->where("sales_order_id", $sales_order->id)
            ->get()

        /* logging */
            ->each(function ($order_detail) use ($sales_order) {
                LogWorkerSalesPoint::updateOrCreate([
                    "sales_order_id" => $sales_order->id,
                ], [
                    "type" => $sales_order->type,
                    "checked_at" => now(),
                ]);
            })

        /* reset point marketing on sales order detail first */
            ->each(function ($order_detail) {
                $order_detail->marketing_point = 0;
                $order_detail->save();
            })

            ->each(function ($order_detail) use (&$point_product_action, $sales_order) {
                $year = confirmation_time($sales_order)->year;
                $quantity = $order_detail->quantity - $order_detail->returned_quantity;
                $point = 0;

                /* point product this year */
                $point_product = $point_product_action($order_detail->product_id, $year)
                    ->where('quantity', '<=', $quantity)
                    ->sortByDesc('minimum_quantity')
                    ->values();

                collect($point_product)->each(function ($point_per_quantity) use (&$point, $order_detail, &$quantity, &$point_detail) {
                    $corresponding_point = floor($quantity / $point_per_quantity->minimum_quantity);
                    $modulo = $quantity % $point_per_quantity->minimum_quantity;
                    $point += $corresponding_point * $point_per_quantity->point;
                    $quantity = $modulo;
                });

                $order_detail->marketing_point = $point;
                $order_detail->save();
            });

        return "product point calculated";
    }
}
