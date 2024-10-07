<?php

namespace Modules\ReceivingGood\Listeners;

use Illuminate\Support\Facades\DB;
use Modules\PickupOrder\Entities\PickupOrder;
use Modules\PickupOrder\Entities\PickupOrderDispatch;

class DipatchStatusUpdateListener
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
        $receiving_good = $event->receiving_good;
        $receiving_good->loadMissing(["deliveryOrder.dispatchOrder", "deliveryOrder.dispatchPromotion"]);
        if ($receiving_good->deleted_at) {
            self::isAnotherRecivingExist($receiving_good);
        } else {
            if ($receiving_good->delivery_status == 2) {
                
                if ($receiving_good->deliveryOrder->dispatchOrder) {
                    self::updatePickupOrderReceived($receiving_good->deliveryOrder->dispatchOrder->id);
                    $receiving_good->deliveryOrder->dispatchOrder->status = "received";
                    $receiving_good->deliveryOrder->dispatchOrder->save();
                    return;
                }

                self::updatePickupOrderReceived($receiving_good->deliveryOrder->dispatchPromotion->id);
                $receiving_good->deliveryOrder->dispatchPromotion->status = "received";
                $receiving_good->deliveryOrder->dispatchPromotion->save();
                return;
            } elseif ($receiving_good->delivery_status == 1) {
                self::isAnotherRecivingExist($receiving_good);
            }
        }
    }

    public static function isAnotherRecivingExist($receiving_good)
    {
        $another_receiving = DB::table('receiving_goods')
            ->whereNull("deleted_at")
            ->where("delivery_order_id", $receiving_good->delivery_order_id)
            ->where("id","!=", $receiving_good->id)
            ->where("delivery_status", "2")
            ->first();

        if ($another_receiving) {
            return;
        } else {
            if ($receiving_good->deliveryOrder->dispatchOrder) {
                $receiving_good->deliveryOrder->dispatchOrder->status = "delivered";
                $receiving_good->deliveryOrder->dispatchOrder->save();
                return;
            }

            $receiving_good->deliveryOrder->dispatchPromotion->status = "delivered";
            $receiving_good->deliveryOrder->dispatchPromotion->save();
            return;
        }
    }

    public static function updatePickupOrderReceived($dispatchId)
    {
        $pickupOrderDispatch = PickupOrderDispatch::where("dispatch_id", $dispatchId)->first();

        if ($pickupOrderDispatch) {
            $dispatchTotal = PickupOrderDispatch::where("pickup_order_id", $pickupOrderDispatch->pickup_order_id)->get()->count();
            $dispatchTotalReceived = PickupOrderDispatch::where("pickup_order_id", $pickupOrderDispatch->pickup_order_id)
                ->whereHas("pickupDispatchAble", function($q){
                    $q->where("status", "received");
                })
                ->get()
                ->count();
            
            if ($dispatchTotal == $dispatchTotalReceived) {
                PickupOrder::where("id", $pickupOrderDispatch->pickup_order_id)->update([
                    "status" => "received"
                ]);
            }
        }
    }
}
