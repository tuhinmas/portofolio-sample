<?php

namespace Modules\SalesOrder\Http\Controllers;

use Orion\Http\Requests\Request;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Modules\SalesOrder\Entities\ExportDirect;
use Modules\SalesOrder\Transformers\ExportDirectCollectionResource;
use Modules\SalesOrder\Transformers\ExportDirectResource;

class SalesOrderDirectExportController extends Controller
{
    use DisableAuthorization;

    protected $model = ExportDirect::class;
    protected $resource = ExportDirectResource::class;
    protected $collectionResource = ExportDirectCollectionResource::class;

    public function alwaysIncludes(): array
    {
        return [
            "order_number",
            "invoice",
            "status",
            "order_date",
            "confirm_date",
            "buyer",
            "seller",
            "marketing",
            "sales_counter",
            "follow_up_days",
            "sub_total",
            "discount",
            "total",
            "ppn",
            "grand_total",
            "payment_method",
            "terbayar",
            "status_bayar",
            "last_pay",
            "catatan"
        ];
    }

    public function includes(): array
    {
        return [
            "order_number",
            "invoice",
            "status",
            "order_date",
            "confirm_date",
            "buyer",
            "seller",
            "marketing",
            "sales_counter",
            "follow_up_days",
            "sub_total",
            "discount",
            "total",
            "ppn",
            "grand_total",
            "payment_method",
            "terbayar",
            "status_bayar",
            "last_pay",
            "catatan"
        ];
    }

    /**
     * scope list
     */
    public function exposedScopes(): array
    {
        return [
            "order_number",
            "invoice",
            "status",
            "order_date",
            "confirm_date",
            "buyer",
            "seller",
            "marketing",
            "sales_counter",
            "follow_up_days",
            "sub_total",
            "discount",
            "total",
            "ppn",
            "grand_total",
            "payment_method",
            "terbayar",
            "status_bayar",
            "last_pay",
            "catatan"
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
            "order_number",
            "invoice",
            "status",
            "order_date",
            "confirm_date",
            "buyer",
            "seller",
            "marketing",
            "sales_counter",
            "follow_up_days",
            "sub_total",
            "discount",
            "total",
            "ppn",
            "grand_total",
            "payment_method",
            "terbayar",
            "status_bayar",
            "last_pay",
            "catatan"
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
            "order_number",
            "invoice",
            "status",
            "order_date",
            "confirm_date",
            "buyer",
            "seller",
            "marketing",
            "sales_counter",
            "follow_up_days",
            "sub_total",
            "discount",
            "total",
            "ppn",
            "grand_total",
            "payment_method",
            "terbayar",
            "status_bayar",
            "last_pay",
            "catatan"
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
            "order_number",
            "invoice",
            "status",
            "order_date",
            "confirm_date",
            "buyer",
            "seller",
            "marketing",
            "sales_counter",
            "follow_up_days",
            "sub_total",
            "discount",
            "total",
            "ppn",
            "grand_total",
            "payment_method",
            "terbayar",
            "status_bayar",
            "last_pay",
            "catatan"
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
