<?php

namespace Modules\DataAcuan\Providers;

use Modules\DataAcuan\Events\MarketingAreaOnChangeEvent;
use Modules\DataAcuan\Listeners\LogFreezeHistoryListener;
use Modules\DataAcuan\Events\FeeWhenFeePositionChangeEvent;
use Modules\DataAcuan\Events\GradingBlockDeleteDealerEvent;
use Modules\DataAcuan\Events\SalesOrderChangeMarketingEvent;
use Modules\DataAcuan\Events\PointInCreatedPointProductEvent;
use Modules\DataAcuan\Events\PointInDeletedPointProductEvent;
use Modules\DataAcuan\Events\PointInUpdatedPointProductEvent;
use Modules\DataAcuan\Listeners\FeeWhenFeePositionChangeListener;
use Modules\DataAcuan\Listeners\GradingBlockDeleteDealerListener;
use Modules\DataAcuan\Listeners\PointInCreatedPointProductListener;
use Modules\DataAcuan\Listeners\PointInDeletedPointProductListener;
use Modules\DataAcuan\Listeners\PointInUpdatedPointProductListener;
use Modules\DataAcuan\Events\FeeWhenFeePositionPercentageChangeEvent;
use Modules\DataAcuan\Listeners\SalesOrderTakeOverByNewMarketingListener;
use Modules\DataAcuan\Listeners\FeeWhenFeePositionPercentageChangeListener;
use Modules\DataAcuan\Listeners\ApplicatorSupervisorOnMarketingAreaChangeListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\DataAcuan\Events\ForecastChangeAreaMarketingEvent;
use Modules\DataAcuan\Listeners\ForecastChangeAreaMarketingListener;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PointInCreatedPointProductEvent::class => [
            PointInCreatedPointProductListener::class
        ], 
        PointInDeletedPointProductEvent::class => [
            PointInDeletedPointProductListener::class
        ],
        PointInUpdatedPointProductEvent::class => [
            PointInUpdatedPointProductListener::class
        ],
        SalesOrderChangeMarketingEvent::class => [
            SalesOrderTakeOverByNewMarketingListener::class
        ],
        GradingBlockDeleteDealerEvent::class => [
           GradingBlockDeleteDealerListener::class 
        ],
        MarketingAreaOnChangeEvent::class => [
            SalesOrderTakeOverByNewMarketingListener::class,
            ApplicatorSupervisorOnMarketingAreaChangeListener::class
        ],
        ForecastChangeAreaMarketingEvent::class => [
            ForecastChangeAreaMarketingListener::class 
        ],
    ];
}
