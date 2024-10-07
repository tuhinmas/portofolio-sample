<?php

namespace Modules\KiosDealer\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\KiosDealer\Entities\DealerV2;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\KiosDealer\Entities\DealerTemp;
use Modules\KiosDealer\Entities\DealerGrading;
use Modules\KiosDealer\Entities\SubDealerTemp;
use Modules\KiosDealer\Entities\CoreFarmerTemp;
use Modules\KiosDealer\Entities\DealerFileTemp;
use Modules\KiosDealer\Policies\DealerV2Policy;
use Modules\KiosDealer\Policies\SubDealerPolicy;
use Modules\KiosDealer\Policies\DealerTempPolicy;
use Modules\KiosDealer\Policies\DealerGradingPolicy;
use Modules\KiosDealer\Policies\SubDealerTempPolicy;
use Modules\KiosDealer\Policies\DealerFileTempPolicy;
use Modules\KiosDealer\Policies\DealerTempFilePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;
use Modules\KiosDealer\Entities\Shop;
use Modules\KiosDealer\Policies\ShopPolicy;

class AuthDealerServiceProvider extends AuthServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Shop::class => ShopPolicy::class,
        DealerTemp::class => DealerTempPolicy::class,
        DealerFileTemp::class => DealerFileTempPolicy::class,
        CoreFarmerTemp::class => DealerFileTempPolicy::class,
        DealerGrading::class => DealerGradingPolicy::class,
        SubDealer::class => SubDealerPolicy::class,
        SubDealerTemp::class => SubDealerTempPolicy::class,
        DealerV2::class => DealerV2Policy::class,
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
