<?php

namespace Modules\PickupOrder\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseHandlerV2;
use Illuminate\Routing\Controller;
use Modules\Personel\Entities\Personel;
use Modules\PickupOrder\Entities\PickupOrder;

class PickupPrintTemplateController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(protected PickupOrder $pickup_order)
    {}

    public function __invoke(Request $request, $pickup_order_id)
    {
        try {
            $pickup_order = $this->pickup_order->query()
                ->with([
                    "warehouse",
                    "proformaReceipt",
                    "armada.personel",
                    "pickupOrderDetails.product",
                    "pickupOrderDispatch.pickupDispatchAble.deliveryOrder",
                ])
                ->findOrFail($pickup_order_id);

            $operation_manager = Personel::query()
                ->whereHas("position", function ($QQQ) {
                    return $QQQ->where("name", "Operational Manager");
                })
                ->where("status", "1")
                ->first();
            $pickup_order["delivery_orders"] = $pickup_order->pickupOrderDispatch->pluck("pickupDispatchAble")->flatten()->pluck("deliveryOrder")->values()->filter();
            $pickup_order["loading_list"] = [
                "product_direct" => $pickup_order->pickupOrderDetails->filter(fn($pickup_detail) => $pickup_detail->detail_type == "dispatch_order")->values(),
                "product_promotion" => $pickup_order->pickupOrderDetails->filter(fn($pickup_detail) => $pickup_detail->detail_type == "dispatch_promotion")->values(),
            ];

            $pickup_order["operational_manager"] = $operation_manager;
            $pickup_order
                ->unsetRelation("pickupOrderDetails")
                ->unsetRelation("pickupOrderDispatch");

            return $this->response("00", "success", $pickup_order);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th);
        }
    }
}
