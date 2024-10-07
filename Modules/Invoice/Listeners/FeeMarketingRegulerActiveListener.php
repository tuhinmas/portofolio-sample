<?php

namespace Modules\Invoice\Listeners;

use Modules\Invoice\Events\FeeMarketingRegulerActiveEvent;
use Modules\Personel\Entities\MarketingFee;
use Modules\SalesOrderV2\Entities\FeeSharing;

class FeeMarketingRegulerActiveListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(FeeSharing $fee_sharing, MarketingFee $marketing_fee)
    {
        $this->fee_sharing = $fee_sharing;
        $this->marketing_fee = $marketing_fee;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(FeeMarketingRegulerActiveEvent $event)
    {
        /**
         * get fee sharing
         */
        $fee_sharing = $this->fee_sharing
            ->where("sales_order_id", $event->invoice->sales_order_id)
            ->get();

        if ($fee_sharing->count() > 0) {

            $fee_sharing->each(function ($fee) use ($event) {
                // $marketing_fee = $this->marketing_fee
                //     ->where("personel_id", $fee->personel_id)
                //     ->where("year", $event->invoice->created_at->format("Y"))
                //     ->where("quarter", $event->invoice->created_at->quarter)
                //     ->first();

                // if ($marketing_fee) {
                //     $marketing_fee->fee_reguler_settle += 10;
                //     $marketing_fee->save();
                // }
            });

             /**
             * get markerting fee
             */
            $marketing_fee = $this->marketing_fee
                ->whereIn("personel_id", $fee_sharing->pluck("personel_id")->toArray())
                ->where("year", $event->invoice->created_at->format("Y"))
                ->where("quarter", $event->invoice->created_at->quarter)
                ->get();

            return $marketing_fee;
        }
    }
}
