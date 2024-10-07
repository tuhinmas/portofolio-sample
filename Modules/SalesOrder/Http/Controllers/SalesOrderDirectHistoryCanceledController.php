<?php

namespace Modules\SalesOrder\Http\Controllers;


use App\Traits\ResponseHandlerV2;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Transformers\SalesOrderDirectHistoryCanceledCollectionResource;
use Modules\SalesOrderV2\Entities\SalesOrderHistoryChangeStatus;

class SalesOrderDirectHistoryCanceledController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(
        protected SalesOrderHistoryChangeStatus $sales_order_history_change_status
    ) {
    }

    public function __invoke(Request $request)
    {
        $direct_sales_order_canceled = $this->sales_order_history_change_status
            ->when($request->has('sales_order_id'),function($q) use ($request){
                return $q->where("sales_order_id",$request->sales_order_id);
            })
            ->with("salesOrder.dealer")
            ->with("salesOrder.invoice")
            ->with("salesOrder.personel.position")
            ->with("personel.position");

        if ($request->disabled_pagination) {
            return $direct_sales_order_canceled
                ->when($request->limit, function ($QQQ) use ($request) {
                    return $QQQ->limit($request->limit);
                })
                ->get();
        }
        $data = $direct_sales_order_canceled->paginate($request->limit ? $request->limit : 15);

        return new SalesOrderDirectHistoryCanceledCollectionResource($data);
    }
}
