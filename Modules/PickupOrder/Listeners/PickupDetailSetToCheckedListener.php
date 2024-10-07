<?php

namespace Modules\PickupOrder\Listeners;

use App\Traits\ResponseHandlerV2;

class PickupDetailSetToCheckedListener
{
    use ResponseHandlerV2;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * pickup order detail set to checked if meet requirement
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $event->pickup_order->load([
            "pickupOrderDetails",
        ]);

        $product_loaded = $event
            ->pickup_order
            ->pickupOrderDetails
            ->each(function ($pickup_detail) {
                $pickup_detail->is_checked = true;
                $pickup_detail->quantity_actual_checked = $pickup_detail->quantity_actual_load;
                $pickup_detail->save();
            });

        $event->pickup_order->status = "checked";
        $event->pickup_order->save();
    }
}
