<?php

namespace Modules\DistributionChannel\Http\Controllers;

use Illuminate\Http\Request;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Contracts\Support\Renderable;
use Modules\DistributionChannel\Entities\DeliveryOrderFile;
use Modules\DistributionChannel\Http\Requests\DeliveryOrderFileRequest;
use Modules\DistributionChannel\Transformers\DeliveryOrderFileResource;

class DeliveryOrderFileController extends Controller
{
    use DisableAuthorization;

    protected $model = DeliveryOrderFile::class;
    protected $request = DeliveryOrderFileRequest::class;
    protected $resource = DeliveryOrderFileResource::class;
    protected $collectionResource = DeliveryOrderFileResource::class;
    

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
            "id_delivery_orders",
            "document",
            "caption"
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
            "id_delivery_orders",
            "document",
            "caption"
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
            "id_delivery_orders",
            "document",
            "caption"
        ];
    }
}
