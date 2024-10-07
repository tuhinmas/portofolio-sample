<?php

namespace Modules\Invoice\Listeners;

use Modules\Invoice\Events\PaymentOnSettleEvent;
use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;

class FeeTargetSharingOriginAsActiveListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(FeeTargetSharingSoOrigin $fee_target_sharing_origin)
    {
        $this->fee_target_sharing_origin = $fee_target_sharing_origin;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(PaymentOnSettleEvent $event)
    {

        if ($event->invoice->created_at->startOfDay()->diffInDays($event->invoice->lastPayment, false) <= maximum_settle_days(now()->format("Y"))) {

            /* get fee tgarget sharing according invoice */
            /* pending */
            // $fee_target_sharing_origin = $this->fee_target_sharing_origin->query()
            //     ->whereHas("salesOrder", function ($QQQ) use ($event) {
            //         return $QQQ->where("sales_order_id", $event->invoice->sales_order_id);
            //     })
            //     ->update([
            //         "is_active" => "1",
            //     ]);

            // return $fee_target_sharing_origin;
        }
        return "out of max settle days";
    }
}
