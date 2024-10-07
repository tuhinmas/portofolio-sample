<?php

namespace Modules\SalesOrder\Traits;

trait ScopeSalesOrderDetail
{
    public function scopeOrderDetailConsideredGetFee($query, $year, $quarter, $type)
    {
        return $query->whereHas("allFeeProduct", function ($QQQ) use ($year, $quarter, $type) {
            return $QQQ
                ->where("year", $year)
                ->where("quartal", $quarter)
                ->where("type", $type);
        });
    }
}
