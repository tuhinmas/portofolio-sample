<?php

namespace Modules\SalesOrder\Actions;

use Modules\SalesOrder\Entities\FeeSharingSoOrigin;

class GetFeeSharingSoOriginByPersonelYearQuarterAction
{
    /*
    |----------------------------------------------------
    | Return order still considered to get fee
    | add to fee total but not to fee active
    |------------------------------------------
     * @param [string] $personel_id
     * @param [int] $year
     * @param [int] $quarter
     * @param [SalesOrder] $sales_order
     * @param [bool] $is_settle in fee aactive check
     * @return void
     */
    public function __invoke($payload)
    {
        extract($payload);
        return FeeSharingSoOrigin::query()
            ->with([
                "salesOrder" => function ($QQQ) {
                    return $QQQ->with([
                        "invoice"
                    ]);
                },
                "salesOrderOrigin" => function ($QQQ) {
                    return $QQQ->with([
                        "direct" => function ($QQQ) {
                            return $QQQ->with([
                                "invoice",
                            ]);
                        },
                    ]);
                },
            ])
            ->when($sales_order, function ($QQQ) use ($sales_order, $personel_id, $is_settle) {
                return $QQQ
                    ->where("sales_order_id", $sales_order->id)
                    ->whereDoesntHave("logMarketingFeeCounter", function ($QQQ) use ($sales_order, $personel_id, $is_settle) {
                        return $QQQ
                        ->where("personel_id", $personel_id)
                        ->when($is_settle, function($QQQ)use($is_settle){
                            return $QQQ->where("is_settle", $is_settle);
                        });
                    });
            })
            ->where("personel_id", $personel_id)
            ->whereYear("confirmed_at", $year)
            ->whereRaw("quarter(confirmed_at) = ?", $quarter)
            ->where("is_checked", true)
            ->whereHas("salesOrder", function ($QQQ) {
                return $QQQ
                    ->consideredOrder()
                    ->isOffice(false);
            })
            ->whereHas("salesOrderDetail", function ($QQQ) use ($year, $quarter) {
                return $QQQ->orderDetailConsideredGetFee($year, $quarter, "1");
            })
            ->get();
    }
}
