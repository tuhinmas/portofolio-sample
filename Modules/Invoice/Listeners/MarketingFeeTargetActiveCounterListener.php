<?php

namespace Modules\Invoice\Listeners;

use Carbon\Carbon;
use Modules\Invoice\Events\FeeTargetMarketingEvent;
use Modules\Personel\Entities\MarketingFee;
use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;
use Modules\SalesOrder\Entities\LogFeeTargetSharing;

class MarketingFeeTargetActiveCounterListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(FeeTargetSharingSoOrigin $fee_target_sharing_origin, MarketingFee $marketing_fee, LogFeeTargetSharing $log_fee_target_sharing)
    {
        $this->fee_target_sharing_origin = $fee_target_sharing_origin;
        $this->log_fee_target_sharing = $log_fee_target_sharing;
        $this->marketing_fee = $marketing_fee;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(FeeTargetMarketingEvent $event)
    {

        if ($event->invoice->lastPayment == "-") {
            return 0;
        }

        /* get fee tgarget sharing according invoice */
        $fee_target_sharing_origin = $this->fee_target_sharing_origin->query()
            ->whereHas("salesOrderDetail", function ($QQQ) use ($event) {
                return $QQQ->where("sales_order_id", $event->invoice->sales_order_id);
            })
            ->where(function ($QQQ) {
                return $QQQ
                    ->whereDoesntHave("salesOrderOrigin")
                    ->orWhereHas("salesOrderOrigin", function ($QQQ) {
                        return $QQQ->where("is_fee_counted", true);
                    });
            })
            ->get();

        $personel_id = $fee_target_sharing_origin
            ->pluck("personel_id")
            ->unique()
            ->reject(function ($personel_id) {
                return !$personel_id;
            });

        $product_id = $fee_target_sharing_origin->pluck("product_id")->unique();

        $year = $event->invoice->created_at->format("Y");
        $quarter = $event->invoice->created_at->quarter;

        /* maximum settle days */
        $maximum_settle_days = maximum_settle_days($year);

        $fee_target_sharing_according_marketing = $this->fee_target_sharing_origin->query()
            ->with([
                "feeProduct" => function ($QQQ) use ($year) {
                    return $QQQ
                        ->where("year", $year)
                        ->where("type", "2");
                },
                "salesOrder" => function ($QQQ) {
                    return $QQQ->with([
                        "invoice",
                        "lastReceivingGoodIndirect",
                    ]);
                },
            ])
            ->feeTargetMarketing($personel_id->toArray(), $year, $quarter)
            ->get()
            ->groupBy("sales_order_id")
            ->reject(function ($origin_per_order, $sales_order_id) use ($year) {
                if ($origin_per_order[0]->salesOrder->type == "1" && $origin_per_order[0]->salesOrder->invoice) {
                    if ($origin_per_order[0]->salesOrder->invoice->payment_time > maximum_settle_days($year)) {
                        return $origin_per_order;
                    }
                }
            })
            ->flatten()
            ->map(function ($origin) {
                $origin->fee_percentage = (string) $origin->fee_percentage;
                $origin->sales_order_id = $origin->salesOrder->id;
                $origin->type = $origin->salesOrder->type;
                return $origin;
            });

        /**
         * fee target total
         */
        $point_target_total = $fee_target_sharing_according_marketing
            ->groupBy([
                function ($val) {return $val->personel_id;},
                function ($val) {return $val->fee_percentage;},
                function ($val) {return $val->product_id;},
                function ($val) {return $val->status_fee_id;},
            ])

            /* point total */
            ->map(function ($origin_per_marketing, $personel_id) use ($maximum_settle_days, $year) {
                $origin_per_marketing = $origin_per_marketing->map(function ($origin_per_fee_percentage, $fee_percentage) use ($personel_id, $maximum_settle_days, $year) {
                    $origin_per_fee_percentage = $origin_per_fee_percentage->map(function ($fee_per_product, $product_id) use ($fee_percentage, $personel_id, $maximum_settle_days, $year) {
                        $fee_per_product = $fee_per_product->map(function ($origin_per_fee_status, $status_fee_id) use ($product_id, $fee_percentage, $personel_id, $maximum_settle_days, $year) {

                            $fee_product = $origin_per_fee_status[0]->feeProduct->where("year", $year)->where("quantity", "<=", collect($origin_per_fee_status)->sum("quantity_unit"))->sortByDesc("quantity")->first();
                            $status_fee_percentage = $origin_per_fee_status[0]->status_fee_percentage;

                            $detail["personel_id"] = $personel_id;
                            $detail["product_id"] = $product_id;
                            $detail["fee_percentage"] = $fee_percentage;
                            $detail["status_fee_percentage"] = $status_fee_percentage;
                            $detail["quantity"] = collect($origin_per_fee_status)->sum("quantity_unit");
                            $detail["fee_target"] = $fee_product ? $fee_product->fee : 0.00;
                            $detail["fee_target_nominal_before_cut"] = $fee_product ? $fee_product->fee * collect($origin_per_fee_status)->sum("quantity_unit") : 0.00;
                            $detail["fee_target_nominal"] = $fee_product ? $fee_product->fee * collect($origin_per_fee_status)->sum("quantity_unit") * $status_fee_percentage / 100 * $fee_percentage / 100 : 0.00;
                            $detail["is_active"] = $origin_per_fee_status[0]->is_active;

                            return $detail;
                        });

                        return $fee_per_product->values();
                    });

                    return $origin_per_fee_percentage->values()->flatten(1);
                });

                return $origin_per_marketing->values()->flatten(1);
            })

            /* sum all fee per marketing*/
            ->map(function ($fee_per_marketing, $personel_id) {
                return collect($fee_per_marketing)->sum("fee_target_nominal");
            })

            /* update marketing fee target */
            ->each(function ($fee_per_marketing, $personel_id) use ($year, $quarter) {

                for ($i = 1; $i < 5; $i++) {
                    $this->marketing_fee->firstOrCreate([
                        "personel_id" => $personel_id,
                        "year" => $year,
                        "quarter" => $i,
                    ], [
                        "fee_target_total" => 0,
                        "fee_target_settle" => 0,
                    ]);
                }

                $marketing_fee = $this->marketing_fee->query()
                    ->where("personel_id", $personel_id)
                    ->where("year", $year)
                    ->where("quarter", $quarter)
                    ->first();

                $this->marketing_fee->updateOrCreate([
                    "personel_id" => $personel_id,
                    "year" => $year,
                    "quarter" => $quarter,
                ], [
                    "fee_target_settle" => $fee_per_marketing,
                ]);

            });

        return $point_target_total;
        return $point_target_settle;
    }
}
