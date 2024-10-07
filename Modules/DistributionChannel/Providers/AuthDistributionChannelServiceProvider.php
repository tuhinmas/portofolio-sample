<?php

namespace Modules\DistributionChannel\Providers;


use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;
use Modules\DistibutionChannel\Policies\ListDispatchOrderPolicy;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\DistributionChannel\Entities\DeliveryOrderFile;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\DistributionChannel\Entities\DispatchOrderDetail;
use Modules\DistributionChannel\Entities\DispatchOrderFile;
use Modules\DistributionChannel\Entities\ListDispatchOrder;
use Modules\DistributionChannel\Policies\DeliveryOrderFilePolicy;
use Modules\DistributionChannel\Policies\DeliveryOrderPolicy;
use Modules\DistributionChannel\Policies\DispatchOrderDetailPolicy;
use Modules\DistributionChannel\Policies\DispatchOrderFilePolicy;
use Modules\DistributionChannel\Policies\DispatchOrderPolicy;

class AuthDistributionChannelServiceProvider extends AuthServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        ListDispatchOrder::class => ListDispatchOrderPolicy::class,
        DispatchOrder::class => DispatchOrderPolicy::class,
        DeliveryOrder::class => DeliveryOrderPolicy::class,
        DeliveryOrderFile::class => DeliveryOrderFilePolicy::class,
        DispatchOrderDetail::class => DispatchOrderDetailPolicy::class,
        DispatchOrderFile::class => DispatchOrderFilePolicy::class
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
    }
}
