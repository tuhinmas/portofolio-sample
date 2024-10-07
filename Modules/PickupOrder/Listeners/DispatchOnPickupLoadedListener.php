<?php

namespace Modules\PickupOrder\Listeners;

class DispatchOnPickupLoadedListener
{
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
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        /**
         * on loaded pickup, dispatch status set to delivered, it mean
         * dispatch also have delivery order
         */
        if ($event->pickup_order->status == "checked") {
            $dispatch = $event
                ->pickup_order
                ->pickupOrderDispatch
                ->pluck("pickupDispatchAble")
                ->flatten()
                ->each(function ($dispatch) use ($event) {
                    if ($dispatch->status != "received") {
                        $dispatch->status = "delivered";
                        $dispatch->save();
                    }
                });
        } 
        
        /**
         * dispatch status rollback to planned on cancel or failed pickup
         */
        elseif (in_array($event->pickup_order->status, ["canceled", "failed"])) {
            $dispatch = $event
                ->pickup_order
                ->pickupOrderDispatch
                ->pluck("pickupDispatchAble")
                ->flatten()
                ->each(function ($dispatch) use ($event) {
                    if ($dispatch->status != "received") {
                        $dispatch->status = "planned";
                        $dispatch->save();
                    }
                });
        }

    }
}
