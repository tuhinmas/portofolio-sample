<?php

namespace Modules\KiosDealer\Http\Controllers;

use Orion\Concerns\DisableAuthorization;
use Orion\Http\Requests\Request;
use Orion\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Support\Renderable;
use Modules\KiosDealer\Entities\CoreFarmerTemp;
use Modules\KiosDealer\Http\Requests\CoreFarmerTempRequest;
use Modules\KiosDealer\Transformers\CoreFarmerTempResource;
use Modules\KiosDealer\Transformers\CoreFarmerTempCollectionResource;

class CoreFarmerTempController extends Controller
{
    use DisableAuthorization;

    protected $model = CoreFarmerTemp::class;
    protected $request = CoreFarmerTempRequest::class;
    protected $resource = CoreFarmerTempResource::class;
    protected $collectionResource = CoreFarmerTempCollectionResource::class;

    /**
    * The relations that are loaded by default together with a resource.
    *
    * @return array
    */
    public function alwaysIncludes() : array
    {
        return [
            'store'
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
            'name', 
            'address',
            'telephone', 
            'store_temp_id'
        ];
    }

    /**
     * The attributes that are used for sorting.
     *
     * @return array
     */
    public function sortableBy() : array
    {
         return [
            'id', 
            'name', 
            'address',
            'telephone', 
            'store_temp_id'
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
        if ($request->has("disabled_pagination")) {
            return $query->get();
        } else {
            return $query
            ->paginate($request->limit > 0 ? $request->limit : 15);
        }
    }
}
