<?php

namespace Modules\DataAcuan\Listeners;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\PaymentMethod;
use Modules\DataAcuan\Events\FeeInDeletedFeeProductEvent;
use Modules\Personel\Entities\MarketingFee;
use Modules\SalesOrderV2\Entities\FeeSharing;
use Modules\SalesOrder\Entities\SalesOrderDetail;

class FeeInDeletedFeeProductListener
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
    public function handle(FeeInDeletedFeeProductEvent $event)
    {

        /**
         * get all sales order detail on this year
         * according fee product updated
         */
        $sales_order_detail = SalesOrderDetail::query()
            ->whereHas("sales_order", function ($QQQ) {
                return $QQQ
                    ->where(function ($QQQ) {
                        return $QQQ
                            ->where(function ($QQQ) {
                                return $QQQ
                                    ->where(function ($QQQ) {
                                        return $QQQ
                                            ->where("type", "1")
                                            ->whereHas("invoice", function ($QQQ) {
                                                return $QQQ->whereYear("created_at", Carbon::now());
                                            });
                                    })
                                    ->orWhere(function ($QQQ) {
                                        return $QQQ
                                            ->where("type", "2")
                                            ->whereYear("date", Carbon::now());
                                    });

                            })
                            ->orWhere(function ($QQQ) {
                                return $QQQ->whereYear("created_at", Carbon::now());
                            });
                    });
            })
            ->where("product_id", $event->fee_product->product_id)
            ->get();

        /**
         * get sales order according sales order detail
         * which product_id is updated
         */
        $sales_order_id = $sales_order_detail
            ->map(function ($detail, $key) {
                return $detail->sales_order_id;
            })
            ->values()
            ->toArray();

        /**
         * update fee marketing on
         * sales order detail
         */
        $sales_order_detail = SalesOrderDetail::query()
            ->whereIn("sales_order_id", $sales_order_id)
            ->update([
                "marketing_fee_reguler" => 0,
            ]);

        /**
         * before recalculate fee sharing, save fee per marketing
         * and reduce all marketing which get fee from
         * sales order this product, no matter
         * was counted or not
         */
        $fee_sharings_counted = FeeSharing::query()
            ->with([
                "salesOrder" => function ($QQQ) {
                    return $QQQ->with([
                        "invoice" => function ($QQQ) {
                            return $QQQ->with("allPayment");
                        },
                    ]);
                },
            ])
            ->where("is_checked", "1")
            ->whereHas("logMarketingFeeCounter", function ($QQQ) {
                return $QQQ->where("type", "reguler");
            })
            ->whereIn("sales_order_id", $sales_order_id)
            ->whereYear("created_at", Carbon::now()->format("Y"))
            ->select("fee_sharings.*", DB::raw("QUARTER(fee_sharings.created_at) as quarter"))
            ->get();

        $marketing_fee_reguler_reduced = $this->marketingFeeAccordingProductUpdated($fee_sharings_counted);

        /**
         * update marketing fee reguler this year, reduce fee reguler according
         * how many sales order affected by its deleted product
         */
        collect($marketing_fee_reguler_reduced)->each(function ($marketing) {
            $marketing_fee = MarketingFee::query()
                ->where("personel_id", $marketing["personel_id"])
                ->where("year", $marketing["year"])
                ->where("quarter", $marketing["quarter"])
                ->first();

            $marketing_fee_update = DB::table('marketing_fee')
                ->where("personel_id", $marketing["personel_id"])
                ->where("year", $marketing["year"])
                ->where("quarter", $marketing["quarter"])
                ->update([
                    "fee_reguler_total" => $marketing_fee->fee_reguler_total - $marketing["fee_reguler_total"],
                    "fee_reguler_settle" => $marketing_fee->fee_reguler_settle - $marketing["fee_reguler_settle"],
                ]);
        });

        /**
         * set fee sharing to unchecked
         * it will be calculated again
         */
        $fee_sharing = DB::table('fee_sharings')
            ->whereIn("sales_order_id", $sales_order_id)
            ->update([
                "fee_shared" => 0,
                "is_checked" => 0,
            ]);

        return $event->fee_product;
    }

    /**
     * populate fe producy according
     * marketing fee
     *
     * @param [type] $marketing_fee_reguler
     * @return void
     */
    public function marketingFeeAccordingProductUpdated($marketing_fee_reguler)
    {

        /**
         * grouping fee by sales order id to get
         * marketing fee
         */
        $marketing_fee_reguler_grouped = $marketing_fee_reguler->groupBy("sales_order_id");

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
                    "type" => $fee_per_quartal[0]->salesOrder->type,
                    "fee_count" => $fee_per_quartal->count(),
                ]));

                return $marketing_fee;
            });

            return $fee_per_marketing;
        });

        return $marketing_fee->sortBy("personel_id");
    }
}
