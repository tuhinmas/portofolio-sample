<?php

namespace Modules\DataAcuan\Listeners;

use Carbon\Carbon;
use Modules\Personel\Entities\LogMarketingFeeCounter;
use Modules\DataAcuan\Events\FeeInCreatedFeeTargetProductEvent;

class FeeInCreatedFeeTargetProductListener
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
    public function handle(FeeInCreatedFeeTargetProductEvent $event)
    {
        /**
         * delete log marketing fee counter
         * and recalculate fee target
         */
        $log_marketing_fee_counter = LogMarketingFeeCounter::query()
            ->whereHas("salesOrder", function ($QQQ) use ($event) {
                return $QQQ
                    ->whereHas("sales_order_detail", function ($QQQ) use ($event) {
                        return $QQQ
                            ->where("product_id", $event->fee_product->product_id);
                    })
                    ->where(function ($QQQ) {
                        return $QQQ
                            ->where(function ($QQQ) {
                                return $QQQ
                                    ->where("type", "1")
                                    ->whereHas("invoice", function ($QQQ) {
                                        return $QQQ
                                            ->whereYear("created_at", Carbon::now());
                                    });
                            })
                            ->orWhere(function ($QQQ) {
                                return $QQQ
                                    ->where("type", "2")
                                    ->whereYear("date", Carbon::now());
                            });

                    });
            })
            ->where("type", "target")
            ->delete();

        return $log_marketing_fee_counter;
    }
}
