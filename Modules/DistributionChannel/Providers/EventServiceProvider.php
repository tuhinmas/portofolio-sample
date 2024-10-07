<?php

namespace Modules\DistributionChannel\Providers;

use Modules\DataAcuan\Events\PointInCreatedPointProductEvent;
use Modules\DataAcuan\Listeners\PointInCreatedPointProductListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\DistributionChannel\Events\DeliveryOrderNotificationEvent;
use Modules\DistributionChannel\Listeners\DeliveryOrderNotificationListener;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        DeliveryOrderNotificationEvent::class => [
            DeliveryOrderNotificationListener::class
        ], 
    ];
}
