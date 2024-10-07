<?php

namespace App\Traits;

use Modules\DataAcuan\Entities\PaymentMethod;
use Modules\SalesOrderV2\Entities\FeeSharing;

/**
 * marketing fee
 */
trait MarketingFee
{
    public function populateMarketingFeeFromFeeSharing($fee_sharings)
    {
        /**
         * grouping fee by sales order id to get
         * marketing fee
         */
        $marketing_fee_reguler_grouped = $fee_sharings
            ->whereNotNull("logMarketingFeeCounter")
            ->where("is_checked", "1")
            ->groupBy("sales_order_id");

        /**
         * after grouping check sales order follow up
         * to fixing fee value fee marketing
         * as purchaser, prchaser = 0
         * on follow up
         */
        $marketing_fee_reguler = $marketing_fee_reguler_grouped->map(function ($fee) {
            $is_follow_up = collect($fee)
                ->filter(function ($fee_order) {
                    if ($fee_order->handover_status == "1") {
                        return $fee_order;
                    }
                })
                ->first();

            if ($is_follow_up) {
                $fee = collect($fee)
                    ->map(function ($fee_order) {
                        if ($fee_order->fee_status == "purchaser") {
                            $fee_order->fee_shared = 0;
                        }
                        return $fee_order;
                    });
            }

            return $fee;
        });

        /* reverse grouping */
        $marketing_fee_reguler = $marketing_fee_reguler->flatten(1);

        /* grouping fee sharing by quarter */
        $marketing_fee_reguler_grouped = $marketing_fee_reguler
            ->sortBy("quarter")
            ->reject(function ($fee) {
                if (!$fee->personel_id) {
                    return $fee;
                }
            })
            ->groupBy([
                function ($val) {return $val->personel_id;},
                function ($val) {return $val->quarter;},
            ]);

        /**
         * marketing fee template
         */
        $marketing_fee = collect();

        /* payment date check */
        $payment_date_maximum = PaymentMethod::orderBy("days", "desc")->first();

        $marketing_fee_reguler_grouped = $marketing_fee_reguler_grouped->map(function ($fee_per_marketing, $personel_id) use (&$marketing_fee, $payment_date_maximum) {
            $fee_per_marketing = collect($fee_per_marketing)->map(function ($fee_per_quartal, $quartal) use ($payment_date_maximum, &$marketing_fee, $personel_id) {

                $fee_reguler_settle_per_quartal = 0;
                $fee_reguler_unsettle_per_quartal = 0;

                $fee_per_quartal = collect($fee_per_quartal)->each(function ($fee) use (&$fee_reguler_settle_per_quartal, &$fee_reguler_unsettle_per_quartal, $payment_date_maximum) {

                    if ($fee->salesOrder) {
                        if ($fee->salesOrder->type == "1") {
                            if ($fee->salesOrder->invoice) {
                                if ($fee->salesOrder->invoice->payment_status == "settle") {

                                    $last_payment = collect($fee->salesOrder->invoice->allPayment)->sortByDesc("payment_date")->first();
                                    $settle_days_count = 0;
                                    if ($last_payment) {
                                        $settle_days_count = $fee->salesOrder->invoice->created_at->startOfDay()->diffInDays($last_payment->payment_date);
                                    } else {
                                        $settle_days_count = $fee->salesOrder->invoice->created_at->startOfDay()->diffInDays($fee->salesOrder->invoice->updated_at);
                                    }

                                    if ($settle_days_count <= ($payment_date_maximum ? $payment_date_maximum->days : 60)) {
                                        $fee_reguler_settle_per_quartal += $fee->fee_shared;
                                    }

                                } else {
                                    $fee_reguler_unsettle_per_quartal += $fee->fee_shared;
                                }
                            }
                        } else {
                            $fee_reguler_settle_per_quartal += $fee->fee_shared;
                        }
                    }
                });

                $marketing_fee->push(collect([
                    "personel_id" => $personel_id,
                    "fee_reguler_total" => $fee_per_quartal->sum("fee_shared"),
                    "fee_reguler_settle" => $fee_reguler_settle_per_quartal,
                    "fee_reguler_unsettle" => $fee_reguler_unsettle_per_quartal,
                    "year" => $fee_per_quartal->first()->created_at->format("Y"),
                    "quarter" => $quartal,
                    "fee_count" => $fee_per_quartal->count(),
                ]));

                return $marketing_fee;
            });

            return $fee_per_marketing;
        });

        return $marketing_fee->sortBy("personel_id");
    }
}
