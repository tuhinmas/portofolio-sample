<?php

namespace Modules\Personel\Actions\Order;

use Modules\SalesOrder\Entities\SalesOrder;

class GetMarketingOrderYearAction
{
    public function __invoke($personel_id, $year, $sales_order = null)
    {
        return SalesOrder::query()
            ->with([
                "salesOrderDetail" => function ($QQQ) {
                    return $QQQ->with([
                        "salesOrder"
                    ]);
                },
                "invoice",
                "dealer",
            ])
            ->marketingSalesByYear($personel_id, $year)
            ->consideredOrder()
            ->when($sales_order, function ($QQQ) use ($sales_order) {
                return $QQQ->where("id", $sales_order->id);
            })
            ->get();
    }
}
