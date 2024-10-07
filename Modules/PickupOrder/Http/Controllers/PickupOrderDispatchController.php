<?php

namespace Modules\PickupOrder\Http\Controllers;

use App\Traits\ResponseHandlerV2;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\PickupOrder\Entities\PickupOrderDispatch;
use Modules\PickupOrder\Repositories\PickupOrderDispatchRepository;

class PickupOrderDispatchController extends Controller
{
    use ResponseHandlerV2;

    public function groupDispatch(Request $request)
    {
        try {
            $repositories = new PickupOrderDispatchRepository();
            $response = $repositories->groupDispatch($request->dispatch_id);
            return $this->response('00', 'Success', $response);
        } catch (\Exception $e) {
            return $this->response('01', 'failed to display data', $e);
        }
    }

    public function unloadDispatch(Request $request, $pickupOrderDispatchId)
    {
        $pickupOrderDispatch = PickupOrderDispatch::query()
            ->with([
                "pickupOrder",
            ])
            ->has("pickupOrder")
            ->findOrFail($pickupOrderDispatchId);
            
        if ($pickupOrderDispatch->pickupOrder->status == "checked") {
            return $this->response("04", "invalid data send", "pickup sudah dicek, tidak bisa direvisi", 422); 
        }

        $deliveryOrder = DeliveryOrder::has("receivingGoodHasReceived")
            ->where(function ($q) use ($pickupOrderDispatch) {
                $q->where("dispatch_order_id", $pickupOrderDispatch->dispatch_id)->orWhere("dispatch_promotion_id", $pickupOrderDispatch->dispatch_id);
            })
            ->exists();

        if ($deliveryOrder) {
            return $this->response("04", "invalid data send", "dispatch sudah diterima !!!", 422);
        }

        try {
            $repositories = new PickupOrderDispatchRepository();
            $response = $repositories->unloadDispatch($pickupOrderDispatchId, $request->all());
            return $this->response('00', 'Success unload dispatch', $response);
        } catch (\Exception $e) {
            return $this->response('01', 'failed to display data', $e);
        }
    }

}
