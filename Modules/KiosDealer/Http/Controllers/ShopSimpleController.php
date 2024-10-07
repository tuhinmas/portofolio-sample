<?php

namespace Modules\KiosDealer\Http\Controllers;

use App\Traits\ResponseHandler;
use Illuminate\Database\Eloquent\Builder;
use Modules\KiosDealer\Entities\ShopSimple;
use Modules\KiosDealer\Transformers\ShopSimpleCollectionResource;
use Modules\KiosDealer\Transformers\ShopSimpleResource;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Orion\Http\Requests\Request;

class ShopSimpleController extends Controller
{
    use ResponseHandler;
    use DisableAuthorization;

    protected $model = ShopSimple::class;
    protected $resource = ShopSimpleResource::class;
    protected $collectionResource = ShopSimpleCollectionResource::class;

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
            "district",
            "district.city",
            "district.city.province",
        ];
    }

    public function exposedScopes(): array
    {
        return [
            "personelBranch",
            "supervisor",
            "filterAll"
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
            "district_id",
            "personel_id",
            "gmaps_link",
            "blocked_at",
            "closed_at",
            "toko_name",
            "telephone",
            "store_id",
            "toko_id",
            "address",
            "model",
            "owner",
        ];
    }

    public function searchable(): array
    {
        return [
            "district_id",
            "personel_id",
            "gmaps_link",
            "toko_name",
            "telephone",
            "store_id",
            "toko_id",
            "address",
            "model",
            "owner",
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
            "district_id",
            "personel_id",
            "gmaps_link",
            "toko_name",
            "telephone",
            "store_id",
            "toko_id",
            "address",
            "model",
            "owner",
        ];
    }

    /**
     * Builds Eloquent query for fetching entities in index method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    protected function buildIndexFetchQuery(Request $request, array $requestedRelations): Builder
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
    protected function runIndexFetchQuery(Request $request, Builder $query, int $paginationLimit)
    {
        if ($request->disabled_pagination) {
            return $query->get();
        }
        return $query->paginate($paginationLimit);
    }
}
