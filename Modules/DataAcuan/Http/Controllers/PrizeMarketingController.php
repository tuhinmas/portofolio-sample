<?php

namespace Modules\DataAcuan\Http\Controllers;

use Carbon\Carbon;

use App\Traits\ResponseHandler;
use Modules\DataAcuan\Entities\PrizeMarketing;
use Orion\Http\Controllers\Controller;
use Modules\DataAcuan\Http\Requests\PrizeMarketingRequest;
use Modules\DataAcuan\Transformers\PrizeMarketingCollectionResource;
use Modules\DataAcuan\Transformers\PrizeMarketingResource;
use Orion\Concerns\DisableAuthorization;

class PrizeMarketingController extends Controller
{
    use ResponseHandler;
    use DisableAuthorization;

    protected $model = PrizeMarketing::class;
    protected $request = PrizeMarketingRequest::class;
    protected $resource = PrizeMarketingResource::class;
    protected $collectionResource = PrizeMarketingCollectionResource::class;

    /*
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     */
    public function alwaysIncludes(): array
    {
        return [
           
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
            'year', 
            "prize",
            "poin",
            'code', 
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
            'year', 
            "prize",
            "poin",
            'code', 
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
            'year', 
            "prize",
            "poin",
            'code', 
            'created_at', 
            'updated_at'
        ];
    }

}
