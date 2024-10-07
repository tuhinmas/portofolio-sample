<?php

namespace Modules\DistributionChannel\Observers;

use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\DistributionChannel\Entities\DeliveryOrderHistory;
use Modules\DistributionChannel\Entities\DeliveryOrderNumber;
use Modules\DistributionChannel\Events\DeliveryOrderNotificationEvent;

class DeliveryOrderObserver
{
    public static $enabled = true;

    public function created(DeliveryOrder $delivery_order)
    {
        if (!self::$enabled) {
            return;
        }

        if ($delivery_order->status == "canceled") {
            DeliveryOrderNumber::query()
                ->where("delivery_order_id", $delivery_order->id)
                ->delete();
        }

        DeliveryOrderNotificationEvent::dispatch($delivery_order);
        if (auth()->check()) {
            DeliveryOrderHistory::create([
                "delivery_order_id" => $delivery_order->id,
                "personel_id" => auth()->user()->personel_id,
                "status" => $delivery_order?->status ?? "accepted",
            ]);
        }
        // DeliveryOrderNotificationJob::dispatchAfterResponse($delivery_order);
    }

    public function updated(DeliveryOrder $delivery_order)
    {
        if (!self::$enabled) {
            return;
        }

        if ($delivery_order->status == "canceled") {
            DeliveryOrderNumber::query()
                ->where("delivery_order_id", $delivery_order->id)
                ->delete();
        }

        $oldStatus = $delivery_order->getOriginal('status');
        $newStatus = $delivery_order->status;
        if ($oldStatus != $newStatus) {
            DeliveryOrderNotificationEvent::dispatch($delivery_order);
            if (auth()->check()) {
                DeliveryOrderHistory::create([
                    "personel_id" => auth()->user()->personel_id,
                    "delivery_order_id" => $delivery_order->id,
                    "status" => $delivery_order->status,
                ]);
            }
            // DeliveryOrderNotificationJob::dispatchAfterResponse($delivery_order);
        }
    }

    public function deleted(DeliveryOrder $delivery_order)
    {
        DeliveryOrderNumber::query()
            ->where("delivery_order_id", $delivery_order->id)
            ->delete();

        if (auth()->check()) {
            DeliveryOrderHistory::create([
                "personel_id" => auth()->user()->personel_id,
                "delivery_order_id" => $delivery_order->id,
                "status" => "deleted",
            ]);
        }
    }
}
