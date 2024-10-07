<?php

namespace Modules\ReceivingGood\Listeners;

use Modules\Invoice\Entities\Invoice;
use Modules\Notification\Entities\NotificationGroupDetail;
use Modules\ReceivingGood\Entities\ReceivingGood;
use Modules\ReceivingGood\Events\DeliveryStatusCheckInReceivingGoodEvent;

class AllProductHasReceivedCheckListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        NotificationGroupDetail $notification_group_detail,
        ReceivingGood $receiving_good,
        Invoice $invoice,
    ) {
        $this->notification_group_detail = $notification_group_detail;
        $this->receiving_good = $receiving_good;
        $this->invoice = $invoice;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(DeliveryStatusCheckInReceivingGoodEvent $event)
    {
        //cek barang promosi atau bukan
        if ($event->receiving_good_detail->product_id == null) {
            return "barang promosi. skip";
        }
        
        $dispatch_order = $event->receiving_good_detail->dispatchOrder;
        if ($dispatch_order) {
            $invoice = $this->invoice
                ->with([
                    "salesOrder",
                    "goodProductHasReceived",
                ])
                ->find($dispatch_order->invoice_id);

            if ($invoice) {

                /* delivery status no need ti check payment status */
                /* pending if ($invoice->payment_status == "settle") { */
                $products = $invoice->salesOrder->sales_order_detail;

                $product_received = collect($invoice->goodProductHasReceived)
                    ->groupBy("product_id")
                    ->map(function ($received, $product_id) use ($products, $event) {
                        $total_product_received["product_id"] = $product_id;
                        $total_product_received["total_received"] = collect($received)->sum("quantity");
                        $total_product_received["total_order"] = $products->where("product_id", $event->receiving_good_detail->product_id)->sum("quantity");
                        return $total_product_received;
                    })
                    ->values();

                $has_received = true;
                $products->each(function ($order_detail) use ($product_received, &$has_received) {
                    $product_received_according_order = $product_received->where("product_id", $order_detail->product_id)->first();

                    if ($product_received_according_order) {
                        if ($product_received_according_order["total_received"] < $order_detail->quantity) {
                            $has_received = false;
                        }
                    } else {
                        $has_received = false;
                    }

                });

                /**
                 * if all product was received
                 */
                if ($has_received) {
                    $invoice->delivery_status = 1;
                    $invoice->save();

                    /* update support task list count */
                    $notif = $this->notification_group_detail->query()
                        ->where("condition->notif_type", 15)
                        ->first();

                    $receiving_goods = $this->receiving_good->query()
                        ->with([
                            "receivingGoodDetail",
                        ])
                        ->whereHas("receivingGoodDetail", function ($QQQ) use ($invoice) {
                            return $QQQ
                                ->whereIn("status", ["broken", "incorrect"])
                                ->whereHas("invoice", function ($QQQ) use ($invoice) {
                                    return $QQQ
                                        ->where("invoices.delivery_status", "2")
                                        ->where("invoice", "!=", $invoice->invoice);
                                });
                        })
                        ->get()
                        ->count();

                    if ($notif) {
                        $notif->task_count = $receiving_goods;
                        $notif->last_check = now();
                        $notif->save();
                    }
                }
                return $invoice->delivery_status;
            } else {
                return "invoice not found";
            }
        }
    }
}
