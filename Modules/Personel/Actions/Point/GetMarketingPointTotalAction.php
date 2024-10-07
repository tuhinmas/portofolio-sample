<?php

namespace Modules\Personel\Actions\Point;

class GetMarketingPointTotalAction
{
    public function __invoke($sales_orders) : int
    {
        return $sales_orders
            ->pluck("salesOrderDetail")
            ->flatten()
            ->pluck("marketing_point")
            ->sum();
    }
}
