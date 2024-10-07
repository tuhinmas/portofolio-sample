<?php

namespace Modules\KiosDealer\Actions\Order;

use Modules\SalesOrder\Entities\SalesOrder;

class GetLastOrderDealerAction
{
    public function __invoke($sales_order, $date = null)
    {

        /* get last order of dealer before this order */
        return SalesOrder::query()
            ->with([
                "invoice",
                "statusFeeShould",
                "confirmedHistory",
            ])
            ->where("sales_orders.id", "!=", $sales_order->id)
            ->where("store_id", $sales_order->store_id)
            ->where("model", $sales_order->model)
            ->limit(25)
            ->orderBy("order_number", "desc")
            ->consideredOrder()
            ->get()
            ->filter(function ($order) use ($date) {
                return confirmation_time($order) <= $date;
            })
            ->sortByDesc(function ($order) use ($sales_order) {
                return confirmation_time($order);
            })
            ->first();
    }
}
