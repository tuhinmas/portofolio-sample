<?php

namespace App\Traits;

use Carbon\Carbon;

/**
 * marketing point trait
 */
trait MarketingPoint
{
    public function populatemarketingPointFromSOD($sales_order_detail)
    {
        $sales_order_grouped = collect($sales_order_detail)->groupBy([
            function ($val) {return $val->sales_order->personel_id;},
            function ($val) {
                if ($val->type == "1") {
                    return $val->sales_order->invoice->created_at->format("Y");
                }
                return Carbon::parse($val->sales_order->date)->format("Y");
            },
        ]);

        $marketing_point = collect();

        collect($sales_order_grouped)->map(function ($sales_order_personel, $personel_id) use (&$marketing_point) {
            $sales_order_personel = collect($sales_order_personel)->map(function ($sales_order_year, $year) use (&$marketing_point, $personel_id) {
                $point_total = collect($sales_order_year)
                    ->whereNotNull("sales_order.logWorkerPointMarketing")
                    ->sum("marketing_point");

                $point_active = collect($sales_order_year)
                    ->whereNotNull("sales_order.logWorkerPointMarketingActive")
                    ->sum("marketing_point");

                $marketing_point->push(collect([
                    "personel_id" => $personel_id,
                    "year" => $year,
                    "point_total_reduced" => $point_total,
                    "point_active_reduced" => $point_active,
                ]));

                return $marketing_point;
            });

            return $sales_order_personel;
        });

        return $marketing_point->sortBy("personel_id");
    }
}
