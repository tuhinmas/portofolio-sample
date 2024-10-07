<?php

namespace Modules\PickupOrder\Providers;

use Modules\PickupOrder\Events\PickupOrderLoadedEvent;
use Modules\PickupOrder\Events\PickupAutoCheckOnLoadEvent;
use Modules\PickupOrder\Listeners\DeliveryOrderGeneratorListener;
use Modules\PickupOrder\Listeners\DispatchOnPickupLoadedListener;
use Modules\PickupOrder\Listeners\PickupDetailSetToCheckedListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PickupOrderLoadedEvent::class => [
            DeliveryOrderGeneratorListener::class,
            DispatchOnPickupLoadedListener::class
        ],
        PickupAutoCheckOnLoadEvent::class => [
            PickupDetailSetToCheckedListener::class,
        ]
    ];
}
