<?php

namespace Modules\DistributionChannel\Listeners;

use Modules\DistributionChannel\Events\CanceledDispatchOrderEvent;

class CanceledDispatchOrderListener
{
    public function handle(CanceledDispatchOrderEvent $dispatchOrder)
    {
        dd($dispatchOrder);
    }
}
