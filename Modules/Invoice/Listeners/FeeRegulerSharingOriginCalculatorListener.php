<?php

namespace Modules\Invoice\Listeners;

use Modules\DataAcuan\Entities\FeePosition;
use Modules\Invoice\Events\FeeMarketingEvent;
use Modules\Personel\Traits\FeeMarketingTrait;
use Modules\SalesOrderV2\Entities\FeeSharingOrigin;
use Modules\Personel\Entities\LogMarketingFeeCounter;

class FeeRegulerSharingOriginCalculatorListener
{
    use FeeMarketingTrait;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        LogMarketingFeeCounter $log_marketing_fee_counter,
        FeeSharingOrigin $fee_sharing_origin,
        FeePosition $fee_position,
    ) {
        $this->log_marketing_fee_counter = $log_marketing_fee_counter;
        $this->fee_sharing_origin = $fee_sharing_origin;
        $this->fee_position = $fee_position;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(FeeMarketingEvent $event)
    {
        /**
         * calculate fee sharing
         */
        return $this->feeSharingOriginCalculator($event->invoice->salesOrder);

        /*
        |--------------------
        | PENDING
        |-------------
         */

        $fee_sharing_origins = $this->fee_sharing_origin
            ->whereHas("salesOrderDetail")
            ->with([
                "salesOrderOrigin",
                "salesOrderDetail" => function ($QQQ) {
                    return $QQQ->with([
                        "feeProduct",
                    ]);
                },
            ])
            ->where("sales_order_id", $event->invoice->sales_order_id)
            ->get();

        $marketing_fee = collect([]);

        /* add quantity */
        $fee_sharing_origins = $fee_sharing_origins
            ->map(function ($origin) {
                $origin["quantity"] = $origin->salesOrderDetail->quantity;
                if ($origin->salesOrderOrigin) {
                    $origin->quantity = $origin->salesOrderOrigin->quantity_from_origin;
                }
                return $origin;
            })
            ->groupBy("sales_order_detail_id")
            ->each(function ($origin_per_detail, $order_detail_id) use (&$marketing_fee) {
                $quantity = $origin_per_detail->first()->quantity;
                $fee = 0;
                $fee_product = null;

                /* if there product has fee product */
                if (!$origin_per_detail->first()->salesOrderDetail->feeProduct) {

                    /* if there have no fee target */
                    $fee_sharing_origin = $this->origin_per_detail
                        ->where("sales_order_detail_id", $origin_per_detail->first()->sales_order_detail_id)
                        ->update([
                            "is_checked" => "1",
                        ]);

                    dump("1");

                    /* retrun true for continue */
                    return true;

                } else {

                    /* get fee target which match with quantity */
                    $fee_product = $origin_per_detail->first()->salesOrderDetail->feeProduct->where('type', 1)->where("year", now()->format("Y"))->first();

                    if (!$fee_product) {

                        /* if there have no fee target */
                        $fee_sharing_origin = $this->fee_sharing_origin
                            ->where("sales_order_detail_id", $origin_per_detail->first()->sales_order_detail_id)
                            ->update([
                                "is_checked" => "1",
                            ]);

                        /* retrun true for continue */
                        return true;
                    }
                }

                $fee_reguler = $quantity * $fee_product->fee;

                /**
             * |-------------------------------------------------------------------------
             * | fee sharing distribution to marketing according fee sharing origin percentage
             * |-------------------------------------------------------------------------
             */

                /* check status fee */
                $is_handover = $origin_per_detail
                    ->filter(function ($fee, $key) use ($fee_reguler) {
                        return $fee->handover_status == 1;
                    })
                    ->first();

                /**
             *
             * fill fee reguler shared and
             * fee target shared
             */
                $sales_counter = $origin_per_detail
                    ->filter(function ($fee, $key) {
                        $fee->unsetRelation("salesOrder");
                        return $fee->fee_status == "sales counter";
                    })
                    ->first();

                /**
             *
             * fee sharing purchaser
             */
                $fee_purchaser = $origin_per_detail
                    ->filter(function ($fee, $key) {
                        $fee->unsetRelation("salesOrder");
                        return $fee->fee_status == "purchaser";
                    })
                    ->first();

                $origin_per_detail->map(function ($fee, $key) use ($fee_reguler, $is_handover, $marketing_fee, $sales_counter, $fee_purchaser) {
                    $marketing_fee_reguler = 0;
                    $marketing_fee_reguler_before_cut = 0;

                    /* sales counter fee check */
                    if ($sales_counter) {

                        /**
                     *
                     * only reguler fee cuts on follow up
                     * target fee does not change
                     */

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
                        "personel_id" => $fee->personel_id,
                        "position_id" => $fee->position_id,
                        "fee_percentage" => $fee->fee_percentage,
                        "fee_shared_before_cut" => $marketing_fee_reguler_before_cut,
                        "fee_shared" => $marketing_fee_reguler,
                        "fee_status" => $fee->fee_status,
                        "sales_order_origin_id" => $fee->sales_order_origin_id,
                        "sales_order_id" => $fee->sales_order_id,
                        "sales_order_detail_id" => $fee->sales_order_detail_id,
                    ]));

                    $fee->unsetRelation("salesOrder");
                });

                /**
             * purchaser fee normal
             */
                $marketing_fee_per_detail = $marketing_fee->sortBy("fee_percentage")->where("sales_order_detail_id", $order_detail_id)->values();
                $fee_purchaser = $marketing_fee_per_detail
                    ->filter(function ($fee, $key) {
                        return $fee["fee_status"] == "purchaser";
                    })
                    ->first();

                /**
             *
             * fee purchaser according fee follow up
             */
                if ($is_handover) {
                    $fee_sharing_status_fee = $marketing_fee_per_detail
                        ->filter(function ($fee, $key) {
                            return $fee["fee_status"] == "marketing fee hand over";
                        })
                        ->first();

                    /* fee follow up */
                    $marketing_fee_per_detail = $marketing_fee_per_detail->map(function ($fee, $key) use ($fee_purchaser, $fee_sharing_status_fee, $sales_counter) {
                        if ($fee["fee_status"] == "marketing fee hand over") {
                            $fee["fee_shared"] = $fee_purchaser["fee_shared"] * ($fee_sharing_status_fee ? $fee_sharing_status_fee["fee_percentage"] / 100 : 1);
                            $fee["fee_shared_before_cut"] = $fee_purchaser["fee_shared"];
                        }

                        return $fee;
                    });
                }

                /**
             * fee sales counter
             */
                $fee_position = $this->fee_position->all();
                $fee_according_position = $fee_position->where("follow_up", "1")->pluck("position_id")->toArray();
                $fee_sales_counter = $marketing_fee_per_detail
                    ->filter(function ($fee, $key) use ($fee_according_position, $is_handover) {
                        if ($is_handover) {
                            return in_array($fee["position_id"], $fee_according_position) && $fee["fee_status"] != "marketing fee hand over" && $fee["fee_status"] != "sales counter";
                        } else {
                            return in_array($fee["position_id"], $fee_according_position) && $fee["fee_status"] != "sales counter";
                        }
                    })
                    ->sum("fee_shared");

                /**
             *
             * update fee sharing origin
             */
                $marketing_fee_per_detail = $marketing_fee_per_detail->map(function ($fee, $key) use ($fee_sales_counter, $fee_reguler) {
                    if ($fee["fee_status"] == "sales counter") {
                        $fee["fee_shared"] = $fee_sales_counter;
                    }

                    /* update fee shairing */
                    $fee_sharing_update = $this->fee_sharing_origin->query()
                        ->where("id", $fee["id"])
                        ->update([
                            "fee_shared" => $fee["fee_shared"],
                            "is_checked" => 1,
                            "total_fee" => $fee_reguler,
                        ]);

                    return $fee;
                });

            });

        return "fee sharing calculated";
        return $marketing_fee->sortBy("fee_percentage");
        // return $marketing_fee->where("sales_order_detail_id", "08874337-1c5c-49fd-84ee-3cb74c21f482")->sortBy("fee_percentage")->count();
    }
}
