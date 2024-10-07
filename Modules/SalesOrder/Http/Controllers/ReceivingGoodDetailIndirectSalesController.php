<?php

namespace Modules\SalesOrder\Http\Controllers;

use App\Traits\MarketingArea;
use App\Traits\ResponseHandler;
use Carbon\Carbon;

use Orion\Http\Requests\Request;
use Orion\Concerns\DisableAuthorization;
use Orion\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Modules\SalesOrder\Entities\ReceivingGoodDetailIndirectSale;
use Modules\SalesOrder\Http\Requests\ReceivingGoodDetailIndirectSalesRequest;
use Modules\SalesOrder\Transformers\ReceivingGoodDetailIndirectSalesCollectionResource;
use Modules\SalesOrder\Transformers\ReceivingGoodDetailIndirectSalesResource;

class ReceivingGoodDetailIndirectSalesController extends Controller
{

    /*
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     * 
     */
    use ResponseHandler;
    use DisableAuthorization;
    use MarketingArea;

    protected $model = ReceivingGoodDetailIndirectSale::class;
    protected $request = ReceivingGoodDetailIndirectSalesRequest::class;
    protected $resource = ReceivingGoodDetailIndirectSalesResource::class;
    protected $collectionResource = ReceivingGoodDetailIndirectSalesCollectionResource::class;


    public function alwaysIncludes(): array
    {
        return [
            
        ];
    }

    public function includes(): array
    {
        return [
            "receivingGoodIndirect"
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
            'id',
            'receiving_good_id',
            'status',
            'note',
            'quantity',
            'quantity_package',
            'product_id',
            'created_at',
            'updated_at',
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
            'receiving_good_id',
            'status',
            'note',
            'quantity',
            'quantity_package',
            'product_id',
            'created_at',
            'updated_at',
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
            'receiving_good_id',
            'status',
            'note',
            'quantity',
            'quantity_package',
            'product_id',
            'created_at',
            'updated_at',
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
            return $query->paginate($request->limit > 0 ? $request->limit : 15);
        }
    }

}
