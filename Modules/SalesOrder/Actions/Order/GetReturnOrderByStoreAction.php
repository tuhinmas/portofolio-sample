<?php

namespace Modules\SalesOrder\Actions\Order;

use Modules\SalesOrder\Entities\SalesOrder;

class GetReturnOrderByStoreAction
{
    public function __invoke($store_id, $date = null)
    {
        return SalesOrder::query()
            ->with([
                "invoice",
            ])
            ->where("store_id", $store_id)
            ->returnedOrderInQuarterByDate($date ?: now())
            ->orderBy("return", "desc")
            ->first();
    }
}
