<?php

namespace Modules\KiosDealer\Http\Controllers;

// use Illuminate\Support\Facades\DB;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Modules\KiosDealer\Entities\Shop;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Modules\KiosDealer\Http\Requests\ShopRequest;
use Modules\KiosDealer\Transformers\ShopResource;
use Modules\View\Http\Requests\ViewDealerRegionRequest;
use Modules\KiosDealer\Transformers\ShopCollectionResource;

class ShopController extends Controller
{
    use ResponseHandler;
    use DisableAuthorization;

    protected $model = Shop::class;
    protected $request = ShopRequest::class;
    protected $resource = ShopResource::class;
    protected $collectionResource = ShopCollectionResource::class;

    /**
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     */
    public function alwaysIncludes(): array
    {
        return [
        ];
    }

    /**
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     */
    public function includes(): array
    {
        return [
            'shopTransaksiThisYear',
            'dealerTemp',
            'subDealerTemp',
            'subDealer',
            'dealer',
        ];
    }

    public function exposedScopes(): array
    {
        return [
            "shopTypeGradingAgencyLevel",
            "shopFilterNameDealeridOwner",
            "personelBranch",
            "supervisor",
            "activeShop"
        ];
    }

    /**
     * The attributes that are used for filtering.
     *
     * @return array
     */
    public function filterableBy(): array
    {
        return [
            "underpayment",
            "store_id",
            "toko_id",
            "toko_name",
            "owner",
            "agency_level_id",
            "agency_level",
            "grading_id",
            "grading",
            "grading_bg_color",
            "grading_bg_gradien",
            "personel_id",
            "personel_position",
            "supervisor_id",
            "province_id",
            "city_id",
            "district_id",
            "sub_region_id",
            "region_id",
            "created_at",
            "status_fee",
            "province",
            "marketing",
            "city",
            "district",
            "sub_region",
            "region",
            "status_fee_name",
            "status_fee_presentage",
            "model",
            "region_id",
            "sub_region_id",
            "sub_total",
            "discount",
            "total",
            "ppn",
            "nominal",
            "payment",
            "payment_status",
            "last_payment",
            "last_transaction",
            "count_transaction",
            "0year_ago",
            "1year_ago",
            "2year_ago",
            "3year_ago",
            "4year_ago"
        ];
    }

    /**
     * The attributes that are used for sorting.
     *
     * @return array
     */
    public function sortableBy(): array
    {
        return [
            "underpayment",
            "store_id",
            "toko_id",
            "toko_name",
            "owner",
            "agency_level_id",
            "agency_level",
            "grading_id",
            "grading",
            "grading_bg_color",
            "grading_bg_gradien",
            "personel_id",
            "personel_position",
            "supervisor_id",
            "province_id",
            "city_id",
            "district_id",
            "sub_region_id",
            "region_id",
            "created_at",
            "status_fee",
            "province",
            "marketing",
            "city",
            "district",
            "sub_region",
            "region",
            "status_fee_name",
            "status_fee_presentage",
            "model",
            "region_id",
            "sub_region_id",
            "sub_total",
            "discount",
            "total",
            "ppn",
            "nominal",
            "payment",
            "payment_status",
            "last_payment",
            "last_transaction",
            "count_transaction",
            "0year_ago",
            "1year_ago",
            "2year_ago",
            "3year_ago",
            "4year_ago"
        ];
    }
}
