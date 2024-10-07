<?php

namespace Modules\DistributionChannel\Observers;

use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\DistributionChannel\Entities\DeliveryOrderNumber;

class DispatchOrderObserver
{
    public function deleted(DispatchOrder $dispatch)
    {
        DeliveryOrderNumber::query()
            ->where("dispatch_order_id", $dispatch->id)
            ->delete();
    }
}
