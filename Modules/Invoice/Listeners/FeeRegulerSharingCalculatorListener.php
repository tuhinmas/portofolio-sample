<?php

namespace Modules\Invoice\Listeners;

use Modules\DataAcuan\Entities\FeePosition;
use Modules\Personel\Entities\LogMarketingFeeCounter;
use Modules\SalesOrderV2\Entities\FeeSharing;

class FeeRegulerSharingCalculatorListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        LogMarketingFeeCounter $log_marketing_fee_counter,
        FeePosition $fee_position,
        FeeSharing $fee_sharing,
    ) {
        $this->log_marketing_fee_counter = $log_marketing_fee_counter;
        $this->fee_position = $fee_position;
        $this->fee_sharing = $fee_sharing;
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
         * get fee sharing data
         */
        $fee_sharing = $this->fee_sharing
            ->with([
                "salesOrder" => function ($QQQ) {
                    return $QQQ
                        ->with("sales_order_detail")
                        ->where(function ($QQQ) {
                            return $QQQ
                                ->whereHas('logWorkerDirectFee');
                        });
                },
                "position" => function ($QQQ) {
                    return $QQQ->with("fee");
                },
                "feePosition",
            ])
            ->where("sales_order_id", $event->invoice->sales_order_id)
            ->whereHas("salesOrder")
            ->get();

        if ($fee_sharing->count() > 0) {
            $fee_reguler = collect($fee_sharing[0]->salesOrder->sales_order_detail)->sum("marketing_fee_reguler");

            /**
             * check status fee
             */
            $is_handover = $fee_sharing
                ->filter(function ($fee, $key) {
                    return $fee->handover_status == 1;
                })
                ->first();

            /**
             * check follow up from sales counter
             */
            $sales_counter = $fee_sharing
                ->filter(function ($fee, $key) {
                    $fee->unsetRelation("salesOrder");
                    return $fee->fee_status == "sales counter";
                })
                ->first();

            /**
             * fee sharing purchaser
             */
            $fee_purchaser = $fee_sharing
                ->filter(function ($fee, $key) {
                    $fee->unsetRelation("salesOrder");
                    return $fee->fee_status == "purchaser";
                })
                ->first();

            /**
             * marketing fee template
             */
            $marketing_fee = collect();

            $fee_sharing = $fee_sharing->map(function ($fee, $key) use ($fee_reguler, $is_handover, $marketing_fee, $sales_counter, $fee_purchaser) {
                $marketing_fee_reguler_before_cut = 0;
                $marketing_fee_reguler = 0;

                /* sales counter fee check */
                if ($sales_counter) {

                    if ($fee->feePosition) {

                        if ($fee->feePosition->follow_up) {
                            if ($fee->fee_status == "marketing fee hand over") {
                                $marketing_fee_reguler_before_cut = $fee_reguler * $fee->fee_percentage / 100;
                                $marketing_fee_reguler = ($fee_reguler * $fee_purchaser->fee_percentage / 100) * ($fee->fee_percentage / 100) * ($sales_counter ? $sales_counter->fee_percentage : 100) / 100;
                            } else {
                                $marketing_fee_reguler_before_cut = $fee_reguler * $fee->fee_percentage / 100;
                                $marketing_fee_reguler = ($fee_reguler * $fee->fee_percentage / 100) * ($sales_counter ? $sales_counter->fee_percentage : 100) / 100;
                            }

                        } else {
                            $marketing_fee_reguler_before_cut = $fee_reguler * $fee->fee_percentage / 100;
                            $marketing_fee_reguler = $fee_reguler * $fee->fee_percentage / 100;
                        }
                    }
                } else {

                    /* status fee dealer / sub dealer check */
                    if ($fee->fee_status == "marketing fee hand over") {
                        $marketing_fee_reguler = ($fee_reguler * $fee_purchaser->fee_percentage / 100) * $fee->fee_percentage / 100;
                    } else {
                        $marketing_fee_reguler = $fee_reguler * $fee->fee_percentage / 100;
                    }
                }

                $marketing_fee->push(collect([
                    "id" => $fee->id,
                    "fee_percentage" => $fee->fee_percentage,
                    "fee_shared_before_cut" => $marketing_fee_reguler_before_cut,
                    "fee_shared" => $marketing_fee_reguler,
                    "fee_status" => $fee->fee_status,
                    "position_id" => $fee->position_id,
                ]));

                $fee->unsetRelation("salesOrder");
            });

            /**
             * fee purchaser according fee follow up
             */
            $fee_sales_counter = 0;
            if ($is_handover) {
                $fee_sharing_status_fee = $marketing_fee
                    ->filter(function ($fee, $key) {
                        return $fee["fee_status"] == "marketing fee hand over";
                    })
                    ->first();

                $fee_for_purchaser = $marketing_fee
                    ->filter(function ($fee, $key) {
                        return $fee["fee_status"] == "purchaser";
                    })
                    ->first();

                /* fee handover store */
                $marketing_fee = $marketing_fee->map(function ($fee, $key) use ($fee_for_purchaser, $fee_sharing_status_fee) {
                    if ($fee["fee_status"] == "marketing fee hand over") {
                        $fee["fee_shared"] = $fee_for_purchaser["fee_shared"] * ($fee_sharing_status_fee ? $fee_sharing_status_fee["fee_percentage"] / 100 : 1);
                        $fee["fee_shared_before_cut"] = $fee_for_purchaser["fee_shared"];
                    }

                    return $fee;
                });
            }

            /**
             * fee sales counter
             */
            $fee_position = $this->fee_position->get();
            $fee_according_position = $fee_position->where("follow_up", "1")->pluck("position_id")->toArray();

            $fee_sales_counter = $marketing_fee
                ->filter(function ($fee, $key) use ($fee_according_position) {
                    return in_array($fee["position_id"], $fee_according_position) && $fee["fee_status"] != "purchaser" && $fee["fee_status"] != "sales counter";
                })
                ->sum("fee_shared");

            $marketing_fee->map(function ($fee, $key) use ($fee_sales_counter) {
                if ($fee["fee_status"] == "sales counter") {
                    $fee["fee_shared"] = $fee_sales_counter;
                }

                /* update fee shairing */
                $fee_sharing_update = FeeSharing::query()
                    ->where("id", $fee["id"])
                    ->update([
                        "fee_shared" => $fee["fee_shared"],
                        "is_checked" => 1,
                    ]);
            });

            // return $marketing_fee;
        }
    }
}
