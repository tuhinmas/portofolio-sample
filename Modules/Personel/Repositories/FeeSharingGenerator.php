<?php

namespace Modules\Personel\Repositories;

use App\Traits\ChildrenList;
use App\Traits\DistributorStock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\FeePosition;
use Modules\Personel\Entities\LogMarketingFeeCounter;
use Modules\Personel\Entities\MarketingFee;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Traits\FeeMarketingTraitV2;
use Modules\SalesOrder\Entities\FeeSharingSoOrigin;
use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;
use Modules\SalesOrder\Entities\LogFeeTargetSharing;
use Modules\SalesOrder\Entities\LogWorkerSalesFee;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;

class FeeSharingGenerator {

    use FeeMarketingTraitV2;
    use DistributorStock;
    use ChildrenList;

    public function __construct(
        FeeTargetSharingSoOrigin $fee_target_sharing_origin,
        LogMarketingFeeCounter $log_marketing_fee_counter,
        LogFeeTargetSharing $log_fee_target_sharing,
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
        $this->log_fee_target_sharing = $log_fee_target_sharing;
        $this->log_worker_sales_fee = $log_worker_sales_fee;
        $this->sales_order_detail = $sales_order_detail;
        $this->fee_sharing_origin = $fee_sharing_origin;
        $this->marketing_fee = $marketing_fee;
        $this->fee_position = $fee_position;
        $this->sales_order = $sales_order;
        $this->personel = $personel;
    }

    public function handle($year, $personelId)
    {
        $quarter = [1,2,3,4];

        FeeSharingSoOrigin::query()
            ->whereYear("confirmed_at", $year)
            ->delete();

        FeeTargetSharingSoOrigin::query()
            ->whereYear("confirmed_at", $year)
            ->delete();

        $feeProductReference = DB::table('fee_products')
            ->where("year", $year)
            ->whereIn("quartal", $quarter)
            ->where("type", "1")
            ->get()
            ->pluck("product_id");

        $feeTargetProductReference = DB::table('fee_products')
            ->where("year", $year)
            ->where("type", "2")
            ->get()
            ->pluck("product_id")
            ->unique()
            ->values();

        Personel::query()->whereHas("position", function ($QQQ) {
                return $QQQ->whereIn("name", marketing_positions());
            })
            ->where("id", $personelId)
            ->orderBy("name")
            ->get()
            ->each(function ($personel) use ($year, $quarter, $feeProductReference, $feeTargetProductReference) {
                SalesOrder::query()
                    ->with([
                        "subDealer",
                        "dealer" => function ($QQQ) {
                            return $QQQ->with([
                                "ditributorContract",
                            ]);
                        },
                        "salesOrderDetail" => function ($QQQ) {
                            return $QQQ->with([
                                "allSalesOrderOrigin",
                            ]);
                        },
                        "salesCounter",
                        "statusFee",
                        "invoice",
                    ])
                    ->where(function ($QQQ) use ($year, $quarter) {
                        return $QQQ
                            ->considerOrderStatusForFeeMarketing()
                            ->where(function ($QQQ) {
                                return $QQQ
                                    ->whereDoesntHave("salesOrderOrigin")
                                    ->orWhereHas("salesOrderOrigin", function ($QQQ) {
                                        return $QQQ->where("is_fee_counted", true);
                                    });
                            })
                            ->where(function ($QQQ) use ($year, $quarter) {
                                return $QQQ
                                    ->where(function ($QQQ) use ($year) {
                                        return $QQQ
                                            ->where("type", "1")
                                            ->whereHas("invoice", function ($QQQ) use ($year) {
                                                return $QQQ
                                                    ->whereYear("created_at", $year);
                                            });
                                    })
                                    ->orWhere(function ($QQQ) use ($year) {
                                        return $QQQ
                                            ->where("type", "2")
                                            ->whereYear("date", $year);
                                    });
                            });
                    })
                    ->where("personel_id", $personel->id)
                    ->get()
                    ->each(function ($order) use ($feeProductReference, $feeTargetProductReference) {
                        collect($order->salesOrderDetail)->each(function ($order_detail) use ($feeProductReference, $feeTargetProductReference) {
                            if (!in_array($order_detail->product_id, $feeProductReference->toArray())) {
                                FeeSharingSoOrigin::query()
                                    ->where("sales_order_detail_id", $order_detail->id)
                                    ->delete();
                            }

                            if (!in_array($order_detail->product_id, $feeTargetProductReference->toArray())) {
                                FeeTargetSharingSoOrigin::query()
                                    ->where("sales_order_detail_id", $order_detail->id)
                                    ->delete();
                            }
                        });
                    })
                    ->each(function ($order) use(&$console) {

                        /* recalculate point per product */
                        $this->feeMarketingPerProductCalculator($order);

                        /* fee sharing generator */
                        $this->feeSharingOriginGenerator($order);

                        /* fee target sharing generator */
                        $this->feeTargetSharingOriginGenerator($order);
                    });
            });
    }

}