<?php

namespace Modules\DistributionChannel\Http\Controllers;

use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\Builder;
use Modules\DistributionChannel\Entities\DispatchOrderFile;
use Modules\DistributionChannel\Transformers\DispatchOrderResource;
use Modules\DistributionChannel\Http\Requests\DispatchOrderFileRequest;
use Modules\DistributionChannel\Transformers\DispatchOrderCollectionResource;
use Orion\Http\Requests\Request;

class DispatchOrderFileController extends Controller
{
    use DisableAuthorization;

    protected $model = DispatchOrderFile::class;
    protected $request = DispatchOrderFileRequest::class;
    protected $resource = DispatchOrderResource::class;
    protected $collectionResource = DispatchOrderCollectionResource::class;
    

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
            "caption",
            "dispatch_orders_id"
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
            "caption",
            "dispatch_orders_id"
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

        /**
     * Builds Eloquent query for fetching entities in index method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    public function buildIndexFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildIndexFetchQuery($request, $requestedRelations);
        return $query;
    }

    /**
     * Runs the given query for fetching entities in index method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int $paginationLimit
     * @return LengthAwarePaginator
     */
    public function runIndexFetchQuery(Request $request, Builder $query, int $paginationLimit)
    {
        if ($request->disabled_pagination) {
            return $query->get();
        }

        return $query
            ->paginate($request->limit > 0 ? $request->limit : 15);
    }
}
