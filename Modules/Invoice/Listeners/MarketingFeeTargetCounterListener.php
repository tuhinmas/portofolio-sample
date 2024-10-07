<?php

namespace Modules\Invoice\Listeners;

use App\Traits\ChildrenList;
use App\Traits\DistributorStock;
use App\Traits\ResponseHandler;
use Modules\DataAcuan\Entities\FeePosition;
use Modules\Invoice\Events\FeeTargetMarketingEvent;
use Modules\Personel\Entities\LogMarketingFeeCounter;
use Modules\Personel\Entities\MarketingFee;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Traits\FeeMarketingTrait;
use Modules\SalesOrder\Entities\FeeSharingSoOrigin;
use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;
use Modules\SalesOrder\Entities\LogWorkerSalesFee;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;

class MarketingFeeTargetCounterListener
{
    use FeeMarketingTrait;
    use DistributorStock;
    use ResponseHandler;
    use ChildrenList;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        FeeTargetSharingSoOrigin $fee_target_sharing_origin,
        LogMarketingFeeCounter $log_marketing_fee_counter,
        LogWorkerSalesFee $log_worker_sales_fee,
        FeeSharingSoOrigin $fee_sharing_origin,
        SalesOrderDetail $sales_order_detail,
        MarketingFee $marketing_fee,
        FeePosition $fee_position,
        SalesOrder $sales_order,
        Personel $personel,
    ) {
        $this->fee_target_sharing_origin = $fee_target_sharing_origin;
        $this->log_marketing_fee_counter = $log_marketing_fee_counter;
        $this->log_worker_sales_fee = $log_worker_sales_fee;
        $this->sales_order_detail = $sales_order_detail;
        $this->fee_sharing_origin = $fee_sharing_origin;
        $this->marketing_fee = $marketing_fee;
        $this->fee_position = $fee_position;
        $this->sales_order = $sales_order;
        $this->personel = $personel;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(FeeTargetMarketingEvent $event)
    {
        $year = $event->invoice->created_at->format("Y");
        $quarter = $event->invoice->created_at->quarter;

        /* get fee tgarget sharing according invoice */
        $fee_target_sharing_origin = $this->fee_target_sharing_origin->query()
            ->whereNotNull("personel_id")
            ->where("sales_order_id", $event->invoice->sales_order_id)
            ->get()
            ->groupBy("sales_order_id")
            ->each(function ($origin_per_order, $sales_order_id) {

                /* fe target */
                $marketing_fee_target_total = $this->feeMarketingTargetTotal($$origin_per_order[0]->personel_id, $request->year, $request->quarter);
                $marketing_fee_target_active = $this->feeMarketingTargetActive($$origin_per_order[0]->personel_id, $request->year, $request->quarter);

                for ($i = 1; $i < 5; $i++) {
                    $this->marketing_fee->firstOrCreate([
                        "personel_id" => $origin_per_order[0]->personel_id,
                        "year" => $year,
                        "quarter" => $i,
                    ], [
                        "fee_reguler_total" => 0,
                        "fee_reguler_settle" => 0,
                        "fee_target_total" => 0,
                        "fee_target_settle" => 0,
                    ]);
                }

                $marketing_fee = $this->marketing_fee->query()
                    ->where("personel_id", $origin_per_order[0]->personel_id)
                    ->where("year", $year)
                    ->where("quarter", $quarter)
                    ->first();

                $marketing_fee->fee_target_total = $marketing_fee_target_total;
                $marketing_fee->fee_target_settle = $marketing_fee_target_active;
                $marketing_fee->save();
            });

        return "fee target counted";

        /*
        |-------------------------------
        | PENDING
        |------------------
         */

        $personel_id = $fee_target_sharing_origin
            ->pluck("personel_id")
            ->unique()
            ->reject(function ($personel_id) {
                return !$personel_id;
            });

        $fee_target_sharing_according_marketing = $this->fee_target_sharing_origin->query()
            ->with([
                "feeProduct" => function ($QQQ) use ($year) {
                    return $QQQ
                        ->where("year", $year)
                        ->where("type", "2");
                },
                "salesOrderDetail" => function ($QQQ) {
                    return $QQQ->with([
                        "sales_order" => function ($QQQ) {
                            return $QQQ->with([
                                "invoice" => function ($QQQ) {
                                    return $QQQ->with([
                                        "lastPayment",
                                    ]);
                                },
                            ]);
                        },
                    ]);
                },
            ])
            ->whereIn("personel_id", $personel_id->toArray())
            ->whereHas("salesOrder")
            ->whereHas("salesOrderDetail")
            ->where("is_returned", "0")
            ->where(function ($QQQ) {
                return $QQQ
                    ->whereDoesntHave("salesOrderOrigin")
                    ->orWhereHas("salesOrderOrigin", function ($QQQ) {
                        return $QQQ->where("is_fee_counted", true);
                    });
            })
            ->whereYear("confirmed_at", $year)
            ->whereRaw("quarter(confirmed_at) = ?", $quarter)
            ->get()
            ->map(function ($origin) {
                $origin->fee_percentage = (string) $origin->fee_percentage;
                $origin->sales_order_id = $origin->salesOrderDetail->sales_order->id;
                $origin->type = $origin->salesOrderDetail->sales_order->type;
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
            ->map(function ($origin_per_marketing, $personel_id) use ($year) {
                $origin_per_marketing = $origin_per_marketing->map(function ($origin_per_fee_percentage, $fee_percentage) use ($personel_id, $year) {
                    $origin_per_fee_percentage = $origin_per_fee_percentage->map(function ($fee_per_product, $product_id) use ($fee_percentage, $personel_id, $year) {
                        $fee_per_product = $fee_per_product->map(function ($origin_per_fee_status, $status_fee_id) use ($product_id, $fee_percentage, $personel_id, $year) {

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
                    "fee_target_total" => $fee_per_marketing,
                ]);

            });

        /**
         * update log fee target
         */

        $log = $fee_target_sharing_according_marketing
            ->pluck("type", "sales_order_id")
            ->unique()
            ->each(function ($type, $sales_order_id) {
                $update_log = $this->log_fee_target_sharing->query()
                    ->firstOrCreate([
                        "sales_order_id" => $sales_order_id,
                        "type" => $type,
                    ]);
            });
        return $point_target_total;
        return $fee_target_sharing_origin;
    }
}
