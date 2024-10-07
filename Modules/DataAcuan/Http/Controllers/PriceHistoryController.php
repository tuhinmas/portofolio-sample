<?php

namespace Modules\DataAcuan\Http\Controllers;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Orion\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\PriceHistory;
use Orion\Concerns\DisableAuthorization;
use Modules\DataAcuan\Transformers\PriceHistoryCollectionResource;
use Modules\DataAcuan\Transformers\PriceHistoryResource;

class PriceHistoryController extends Controller
{
    use DisableAuthorization;
    use ResponseHandler;

    protected $model = PriceHistory::class;
    protected $resource = PriceHistoryResource::class;
    protected $collectionResource = PriceHistoryCollectionResource::class;

    public function alwaysIncludes(): array
    {
        return [];
    }

    public function includes(): array
    {
        return [
            "priceBelongTo",
            "priceBelongTo.agencyLevel",
            "agencyLevel",
            "product"
        ];
    }
    /**
     * scope list
     */
    public function exposedScopes(): array
    {
        return [];
    }

    /**
     * filetr list
     *
     * @return array
     */
    public function filterableBy(): array
    {
        return [
            "priceBelongTo",
            "price_id",
            "agency_level_id",
            "product_id"
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
            "price",
            "price_id",
            "product_id"
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
            "price",
            "price_id",
            "product_id",
            "created_at"
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
        return parent::buildIndexFetchQuery($request, $requestedRelations);
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
        if ($request->has("disabled_pagination") && $request->disable_pagination == true) {
            return $query->get();
        } else {
            return $query->paginate($request->limit > 0 ? $request->limit : 15);
        }
    }


    /**
     * Fills attributes on the given entity and stores it in database.
     *
     * @param Request $request
     * @param Model $entity
     * @param array $attributes
     */
    public function performStore(Request $request, Model $entity, array $attributes): void
    {
        $entity->fill($attributes);
        $entity->save();
    }

    /**
     * Builds Eloquent query for fetching entities in index method.
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    public function buildUpdateFetchQuery(Request $request, array $requestedRelations): Builder
    {
        $query = parent::buildUpdateFetchQuery($request, $requestedRelations);
        return $query;
    }
}
