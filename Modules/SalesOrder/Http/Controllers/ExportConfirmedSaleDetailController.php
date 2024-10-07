<?php

namespace Modules\SalesOrder\Http\Controllers;

use Orion\Http\Requests\Request;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Modules\SalesOrder\Entities\ExportConfirmedSaleDetail;
use Modules\SalesOrder\Http\Requests\ExportConfirmedSaleDetailRequest;
use Modules\SalesOrder\Transformers\ExportConfirmedSaleDetailResource;
use Modules\SalesOrder\Transformers\ExportConfirmedSaleDetailCollectionResource;

class ExportConfirmedSaleDetailController extends Controller
{
    use DisableAuthorization;

    protected $model = ExportConfirmedSaleDetail::class;
    protected $request = ExportConfirmedSaleDetailRequest::class;
    protected $resource = ExportConfirmedSaleDetailResource::class;
    protected $collectionResource = ExportConfirmedSaleDetailCollectionResource::class;

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
     * scope list
     */
    public function exposedScopes(): array
    {
        return [

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
            "sales_order_id",
            "no_order",
            "quantity",
            "store_id",
            "product",
            "retur",
            "stock",
            "unit",
            "inv",
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
            "sales_order_id",
            "no_order",
            "quantity",
            "product",
            "retur",
            "stock",
            "unit",
            "inv",
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
            "sales_order_id",
            "no_order",
            "quantity",
            "product",
            "retur",
            "stock",
            "unit",
            "inv",
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
            return $query->withAggregate("salesOrderConfirmed", "sub_total")->get();
        }
        return $query->paginate($paginationLimit);
    }
}
