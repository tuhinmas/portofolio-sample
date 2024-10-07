<?php

namespace Modules\DataAcuan\Providers;

use Modules\DataAcuan\Entities\Fee;
use Modules\DataAcuan\Entities\Ppn;
use Modules\DataAcuan\Entities\Plant;
use Modules\DataAcuan\Entities\Region;
use Illuminate\Support\ServiceProvider;
use Modules\DataAcuan\Entities\StatusFee;
use Modules\DataAcuan\Entities\SubRegion;
use Modules\DataAcuan\Policies\FeePolicy;
use Modules\DataAcuan\Policies\PpnPolicy;
use Modules\DataAcuan\Entities\FeeReguler;
use Modules\DataAcuan\Entities\FeeFollowUp;
use Modules\DataAcuan\Entities\FeePosition;
use Modules\DataAcuan\Policies\PlantPolicy;
use Modules\DataAcuan\Entities\PointProduct;
use Modules\DataAcuan\Policies\RegionPolicy;
use Modules\DataAcuan\Entities\MarketingPoin;
use Modules\DataAcuan\Entities\PlantCategory;
use Modules\DataAcuan\Policies\GradingPolicy;
use Modules\DataAcuan\Entities\ProformaReceipt;
use Modules\DataAcuan\Policies\StatusFeePolicy;
use Modules\DataAcuan\Policies\SubRegionPolicy;
use Modules\DataAcuan\Policies\FeeRegulerPolicy;
use Modules\DataAcuan\Entities\MarketingAreaCity;
use Modules\DataAcuan\Policies\FeeFollowUpPolicy;
use Modules\DataAcuan\Policies\FeePositionPolicy;
use Modules\DataAcuan\Policies\PointProductPolicy;
use Modules\DataAcuan\Entities\DealerPaymentMethod;
use Modules\DataAcuan\Policies\MarketingPoinPolicy;
use Modules\DataAcuan\Policies\PlantCategoryPolicy;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\DataAcuan\Policies\ProformaReceiptPolicy;
use Modules\DataAcuan\Policies\MarketingAreaCityPolicy;
use Modules\DataAcuan\Policies\DealerPaymentMethodPolicy;
use Modules\DataAcuan\Policies\MarketingAreaRegionPolicy;
use Modules\DataAcuan\Policies\MarketingAreaDistrictPolicy;
use Modules\DataAcuan\Policies\MarketingAreaSubRegionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;
use Modules\DataAcuan\Entities\Budget;
use Modules\DataAcuan\Entities\Driver;
use Modules\DataAcuan\Entities\ProductMandatory;
use Modules\DataAcuan\Entities\Warehouses;
use Modules\DataAcuan\Policies\BudgetPolicy;
use Modules\DataAcuan\Policies\DriverPolicy;
use Modules\DataAcuan\Policies\ProductMandatoryPolicy;
use Modules\DataAcuan\Policies\WarehousePolicy;

class AuthDataACuanServiceProvider extends AuthServiceProvider
{
     /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        PlantCategory::class => PlantCategoryPolicy::class,
        Plant::class => PlantPolicy::class,
        Grading::class => GradingPolicy::class,
        Region::class => MarketingAreaRegionPolicy::class,
        SubRegion::class => SubRegionPolicy::class,
        MarketingAreaCity::class => MarketingAreaCityPolicy::class,
        MarketingAreaDistrict::class => MarketingAreaDistrictPolicy::class,
        DealerPaymentMethod::class => DealerPaymentMethodPolicy::class,
        Fee::class => FeePolicy::class,
        FeePosition::class => FeePositionPolicy::class,
        StatusFee::class => StatusFeePolicy::class,
        FeeFollowUp::class => FeeFollowUpPolicy::class,
        PointProduct::class => PointProductPolicy::class,
        Ppn::class => PpnPolicy::class,
        MarketingPoin::class => MarketingPoinPolicy::class,
        ProformaReceipt::class => ProformaReceiptPolicy::class,
        ProductMandatory::class => ProductMandatoryPolicy::class,
        Driver::class => DriverPolicy::class,
        Warehouses::class => WarehousePolicy::class
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
