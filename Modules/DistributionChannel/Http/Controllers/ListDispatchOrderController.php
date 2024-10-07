<?php

namespace Modules\DistributionChannel\Http\Controllers;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Orion\Http\Controllers\Controller;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\DistributionChannel\Entities\ListDispatchOrder;
use Modules\DistributionChannel\Http\Requests\ListDispatchOrderRequest;
use Modules\DistributionChannel\Transformers\ListDispatchOrderResource;
use Modules\DistributionChannel\Transformers\ListDispatchOrderCollectionResource;

class ListDispatchOrderController extends Controller
{
    use ResponseHandler;
    use DisableAuthorization;

    protected $model = ListDispatchOrder::class;
    protected $request = ListDispatchOrderRequest::class;
    protected $resource = ListDispatchOrderResource::class;
    protected $collectionResource = ListDispatchOrderCollectionResource::class;

    /*
     * The relations that are loaded by default together with a resource.
     *
     * @return array
     */
    public function alwaysIncludes(): array
    {
        return [
            "invoice.salesOrder.dealerv2",
            "driver",
            "detail_dispatch_order"
        ];
    }


    public function includes(): array
    {
        return [];
    }


    /**
     * The list of available query scopes.
     *
     * @return array
     */
    public function exposedScopes(): array
    {
        return [
            "hasHystoryDispatch"
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
            "dispatch_order_number",
            'dispatch_date',
            "invoice.invoice",
            'destination',
            'quantity',
            'weight',
            'status'
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
            'destination',
            "dispatch_order_number",
            'dispatch_date',
            'quantity',
            'weight',
            'status'
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
            'destination',
            "dispatch_order_number",
            'dispatch_date',
            'quantity',
            'weight',
            'status'
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
        return $query->whereHas("invoice");
    }

    /**
     * Runs the given query for fetching entities in index method.
     *
     * @param Request $request
     * @param Builder $query
     * @param int $paginationLimit
     * @return LengthAwarePaginator
     */
    public function runIndexFetchQuery(Request $request, Builder $query, int $paginationLimit): LengthAwarePaginator
    {
        return $query->paginate($paginationLimit);
    }
     

    public function dispatchOrder($id)
    {
        try {
            $data = [];

            $detail = ListDispatchOrder::with('invoice')->find($id);
            // return $detail;
            // return $detail->invoice->salesOrder->sales_order_detail;
            //$details = collect($detail);


            foreach ($detail->invoice->salesOrder->sales_order_detail as $det => $value) {
                $data["detail_muatan"][$value->product->id] = $value;
                $data["detail_muatan"][$value->product->id]["total_quantity"] = 0;
            }

            // return $data;

            $dispatch_order = DispatchOrder::where('id_list_dispatch_order', $id)->get();

            $dispatch_order_group = collect($dispatch_order)->groupBy('id_product');
            // return $dispatch_order_group;
            $total = 0;
            foreach ($dispatch_order_group as $product => $products) {
                $total += $products[0]->quantity_packet_to_send;
                $data["detail_muatan"][$products[0]->id_product]["total_quantity"] = $total;
            }
            return $data;

            // $total = 0;
            $sum = collect($dispatch_order)->sum('quantity_packet_to_send');
            $data["detail_muatan"]["total_quantity"] = $sum;
        } catch (\Throwable $th) {
            return $this->response("01", "failed to get detail muatan", $th->getMessage());
        }
    }

}
