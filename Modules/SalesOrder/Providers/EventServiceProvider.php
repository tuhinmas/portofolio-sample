<?php

namespace Modules\SalesOrder\Providers;

use Modules\SalesOrder\Events\FeeSharing;
use Modules\SalesOrder\Events\SalesOrderReturn;
use Modules\SalesOrder\Events\DeletedProductEvent;
use Modules\SalesOrder\Events\UpdatedProductEvent;
use Modules\SalesOrder\Events\DeletedSalesOrderEvent;
use Modules\SalesOrder\Listeners\FeeSharingMarketing;
use Modules\SalesOrder\Listeners\DeletedProductListener;
use Modules\SalesOrder\Listeners\UpdatedProductListener;
use Modules\SalesOrder\Events\FeeMarketingPerProductEvent;
use Modules\SalesOrder\Listeners\SalesOrderReturnListener;
use Modules\SalesOrder\Listeners\FeeMarketingPerProductListener;
use Modules\SalesOrder\Listeners\MarketingFeeWhenDeletedSalesOrderListener;
use Modules\SalesOrder\Listeners\MarketingPointWhenDeletedSalesOrderListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\SalesOrder\Events\DirectSalesAbandonedNotificationEvent;
use Modules\SalesOrder\Events\DirectSalesConfirmedNotificationEvent;
use Modules\SalesOrder\Events\DirectSalesRejectedNotificationEvent;
use Modules\SalesOrder\Listeners\DirectSalesAbondonedListener;
use Modules\SalesOrder\Listeners\SalesOrderDirectConfirmedListener;
use Modules\SalesOrder\Listeners\SalesOrderDirectRejectedListener;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        FeeSharing::class => [
            FeeSharingMarketing::class
        ],
        SalesOrderReturn::class => [
            SalesOrderReturnListener::class
        ],
        DeletedProductEvent::class => [
            DeletedProductListener::class
        ],
        UpdatedProductEvent::class => [
            UpdatedProductListener::class
        ],
        DeletedSalesOrderEvent::class => [
            MarketingFeeWhenDeletedSalesOrderListener::class,
            MarketingPointWhenDeletedSalesOrderListener::class
        ],
        DirectSalesConfirmedNotificationEvent::class => [
            SalesOrderDirectConfirmedListener::class
        ],
        DirectSalesRejectedNotificationEvent::class => [
            SalesOrderDirectRejectedListener::class
        ],
        FeeMarketingPerProductEvent::class => [
            FeeMarketingPerProductListener::class
        ],
        DirectSalesAbandonedNotificationEvent::class => [
            DirectSalesAbondonedListener::class
        ]
    ];
}
