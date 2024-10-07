<?php

namespace Modules\ReceivingGood\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseHandlerV2;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\DistributionChannel\Actions\GetProductDispatchAction;

class ReceivingGoodConsideringFormDataController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(
        protected DeliveryOrder $delivery_order,
    ) {}

    public function __invoke(Request $request, GetProductDispatchAction $product_dispatch)
    {
        $request->validate([
            "delivery_order_id" => "required",
        ]);
        
        $delivery_order = DeliveryOrder::findOrFail($request->delivery_order_id);

        try {
            return $this->response("00", "success", $product_dispatch($request->delivery_order_id));
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th);
        }
    }
}
