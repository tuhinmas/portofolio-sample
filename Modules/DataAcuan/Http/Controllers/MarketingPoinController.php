<?php

namespace Modules\DataAcuan\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseHandler;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Contracts\Support\Renderable;
use Modules\DataAcuan\Entities\MarketingPoin;
use Modules\DataAcuan\Policies\MarketingPoinPolicy;
use Modules\DataAcuan\Http\Requests\MarketingPoinRequest;
use Modules\DataAcuan\Transformers\MarketingPointResource;
use Modules\DataAcuan\Transformers\MarketingPointCollectionResource;

class MarketingPoinController extends Controller
{
    use ResponseHandler;
    use DisableAuthorization;

    protected $model = MarketingPoin::class;
    protected $request = MarketingPoinRequest::class;
    protected $resource = MarketingPointResource::class;
    protected $collectionResource = MarketingPointCollectionResource::class;

    
    /*
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     */
    public function alwaysIncludes(): array
    {
        return [
            "product"
        ];
    }


    public function includes(): array
    {
        return [
        ];
    }

    /**
    * The attributes that are used for filtering.
    *
    * @return array
    */
    public function filterableBy() : array
    {
        return [
            'id', 
            'point', 
            "product_id",
            "quantity",
            "start_date",
            "end_date", 
            'created_at', 
            'updated_at'
        ];
    }


    /**
     * search by
     *
     * @return array
     */
    public function searchableBy(): array
    {
        return [
            'id', 
            'point', 
            "product_id",
            "quantity",
            "start_date",
            "end_date", 
            'created_at', 
            'updated_at'
        ];
    }

      /**
     * sort by list
     *
     * @return array
     */
    public function sortableBy(): array
    {
        return [
            'id', 
            'point', 
            "product_id",
            "quantity",
            'period_date', 
            'created_at', 
            'updated_at'
        ];
    }
}
