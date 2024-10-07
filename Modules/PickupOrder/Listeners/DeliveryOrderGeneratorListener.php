<?php

namespace Modules\PickupOrder\Listeners;

use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\PickupOrder\Actions\GenerateDeliveryOrderAction;
use Modules\PickupOrder\Entities\PickupOrderDispatch;

class DeliveryOrderGeneratorListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(PickupOrderDispatch $pickup_dispatch)
    {
        $this->pickup_dispatch = $pickup_dispatch;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        if ($event->pickup_order->status == "checked") {

            $event->pickup_order->load("pickupOrderDispatch.pickupDispatchAble");
            $dispatch = $event
                ->pickup_order
                ->pickupOrderDispatch
                ->pluck("pickupDispatchAble")
                ->flatten()
                ->each(function ($dispatch) use ($event) {
                    $is_promotion = true;
                    if ($dispatch instanceof DispatchOrder) {
                        $is_promotion = false;
                    }

                    (new GenerateDeliveryOrderAction)($dispatch->id, $is_promotion, $event->pickup_order->delivery_date);
                });
        }
    }
}
