<?php

namespace Modules\SalesOrder\Http\Controllers;


use App\Traits\ResponseHandlerV2;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\SalesOrder\Entities\SalesOrder;

class SalesOrderDirectDraftController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(
        protected SalesOrder $sales_order
    ) {
    }

    public function __invoke(Request $request)
    {
        $direct_sales_draft = $this->sales_order->where("type", "1")
            ->select("id", "store_id", "status", "type", "total", "sub_total", "discount", "status", "order_number", "updated_at")
            ->withAggregate('dealer', 'name')
            ->withAggregate('subDealer', 'name')
            ->with("sales_order_detail_product")
            ->with("invoiceOnly")
            // ->whereHas("sales_order_detail_product")
            ->whereIn("status", ["draft", "submited"])->when($request->has("store_id"), function ($query) use ($request) {
                return $query->where("store_id", $request->store_id);
            })->orderByDesc("updated_at")->get();

        return $this->response('00', 'success, get data latest direct sales draft or submitted', $direct_sales_draft);
    }
}
