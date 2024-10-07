<?php

namespace Modules\Invoice\Listeners;

use Illuminate\Support\Facades\Auth;
use Modules\Invoice\Events\PaymentOnSettleEvent;
use Modules\SalesOrderV2\Entities\SalesOrderHistoryChangeStatus;

class DeliveryStatusCheckListener
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
    public function handle(PaymentOnSettleEvent $event)
    {
        if ($event->invoice->goodProductHasReceived->count() > 0) {
            $products = $event->invoice->salesOrderOnly->salesOrderDetail;

            $received_product = $event->invoice->goodProductHasReceived
                ->groupBy("product_id")
                ->map(function ($order_detail, $product_id) {
                    $received["product_id"] = $product_id;
                    $received["received"] = collect($order_detail)->sum("quantity");

                    return $received;
                })
                ->values();

            $order_has_delivered = true;
            if (collect($products)->count() > 0) {
                $products->each(function ($product) use ($received_product, &$order_has_delivered) {
                    $total_product_received = $received_product->where("product_id", $product->product_id)->first();
                    if ($total_product_received) {
                        if ($total_product_received["received"] < $product->quantity) {
                            $order_has_delivered = false;
                        }
                    }
                });

            }

            /**
             * update delivery status
             */
            if ($order_has_delivered) {
                $event->invoice->delivery_status = "1";
                $event->invoice->save();
                return "delivery order done";
            }
            
            
            SalesOrderHistoryChangeStatus::create([
                "sales_order_id" => $event->invoice->sales_order_id,
                "type" => "1",
                "delivery_order_id" => null,
                "status" => "done_delivery_order",
                "personel_id" => Auth::user()->personel_id,
                "note" => "Pengiriman selesai pada ".$event->invoice->lastReceivingGood->created_at,
            ]);

            return $received_product;
        }
    }
}
