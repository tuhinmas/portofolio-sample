<?php

namespace Modules\ReceivingGood\Providers;

use Modules\ReceivingGood\Events\DipatchOnReceivedEvent;
use Modules\ReceivingGood\Listeners\DipatchStatusUpdateListener;
use Modules\ReceivingGood\Listeners\AllProductHasReceivedCheckListener;
use Modules\ReceivingGood\Events\DeliveryStatusCheckInReceivingGoodEvent;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        DeliveryStatusCheckInReceivingGoodEvent::class => [
            AllProductHasReceivedCheckListener::class
        ],
        DipatchOnReceivedEvent::class => [
            DipatchStatusUpdateListener::class
        ]
    ];
}
