<?php

namespace Modules\SalesOrder\Http\Controllers;

use App\Traits\ResponseHandlerV2;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\SalesOrder\Transformers\FeePerProductOrderResource;

class FeePerProductOrderController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(
        protected SalesOrderDetail $sales_order_detail,
        protected SalesOrder $sales_order,
    ) {}

    public function __invoke(Request $request, $sales_order_id, $personel_id)
    {
        $request->merge([
            "personel_id" => $personel_id
        ]);
        
        $this->sales_order->findOrFail($sales_order_id);
        try {
            $sales_order_details = $this->sales_order_detail->query()
                ->with([
                    "product",
                    "salesOrder",
                    "feeSharingOrigin",
                ])
                ->where("sales_order_id", $sales_order_id)
                ->get();

            return new FeePerProductOrderResource($sales_order_details);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th);
        }
    }
}
