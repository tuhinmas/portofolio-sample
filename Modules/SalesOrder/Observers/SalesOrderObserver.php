<?php

namespace Modules\SalesOrder\Observers;

use Modules\SalesOrderV2\Entities\SalesOrderHistoryChangeStatus;
use Modules\SalesOrder\Entities\SalesOrder;

class SalesOrderObserver
{
    public function created(SalesOrder $sales_order)
    {
        $sales_order->refresh();
        SalesOrderHistoryChangeStatus::create([
            "sales_order_id" => $sales_order->id,
            "type" => $sales_order->type,
            "status" => $sales_order->status,
            "personel_id" => $sales_order->personel_id,
            "note" => "set " . $sales_order->status . " in " . now()->format("Y-m-d"),
        ]);
    }
}
