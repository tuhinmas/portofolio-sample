<?php

namespace Modules\PickupOrder\Http\Controllers;

use App\Traits\ResponseHandlerV2;
use Illuminate\Routing\Controller;
use Modules\PickupOrder\Entities\PickupOrder;

class PickupOrderCounterController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(protected PickupOrder $pickup_order)
    {}

    public function __invoke()
    {
        try {
            $pickup_count = $this->pickup_order->query()
                ->today()
                ->whereIn("status", ["planned", "revised"])
                ->count();

            return $this->response("00", "success", [
                "pickup_deliver_today" => $pickup_count,
            ]);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th);
        }
    }
}
