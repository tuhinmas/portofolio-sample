<?php

namespace Modules\Personel\Providers;

use Modules\Personel\Events\PersonelActiveEvent;
use Modules\Personel\Events\PersonelFreezeEvent;
use Modules\Personel\Events\PersoneJoinDateEvent;
use Modules\Personel\Events\PersonelInactiveEvent;
use Modules\Personel\Listeners\InactiveMdmListener;
use Modules\Personel\Listeners\PersonelFreezeListener;
use Modules\Personel\Listeners\InactiveMdmOrMmListener;
use Modules\Personel\Listeners\HandoverOrderToSpvListener;
use Modules\Personel\Listeners\MakePersonelActiveListener;
use Modules\Personel\Listeners\RecalculateFeeMarketingListener;
use Modules\Personel\Listeners\PersonelJoinDateOnChangeListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Personel\Events\PersonelUpdateFromHistoryPersonelStatusEvent;
use Modules\Personel\Listeners\PersonelUpdateFromHistoryPersonelStatusListener;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PersonelActiveEvent::class => [
            MakePersonelActiveListener::class,
        ],
        PersonelFreezeEvent::class => [
            PersonelFreezeListener::class
        ],
        PersoneJoinDateEvent::class => [
            PersonelJoinDateOnChangeListener::class,
            RecalculateFeeMarketingListener::class
        ],
        PersonelInactiveEvent::class => [
            HandoverOrderToSpvListener::class,
        ],
        PersonelUpdateFromHistoryPersonelStatusEvent::class => [
            PersonelUpdateFromHistoryPersonelStatusListener::class
        ]
    ];
}
