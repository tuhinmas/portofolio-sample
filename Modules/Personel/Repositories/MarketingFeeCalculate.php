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
use Spatie\Activitylog\Contracts\Activity;

class MarketingFeeCalculate {

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

        Personel::whereHas("position", function ($QQQ) {
                return $QQQ->whereIn("name", marketing_positions());
            })
            ->where("id", $personelId)
            ->orderBy("name")
            ->get()
            ->each(function ($personel) use ($year, $quarter) {
                /* recalculate fee per product */
                /* fee sharring origin generator */
                /* fee sharing calculator */
                $sales_orders = SalesOrder::query()
                    ->with([
                        "dealer" => function ($QQQ) {
                            return $QQQ->with([
                                "ditributorContract",
                            ]);
                        },
                        "salesOrderDetail",
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
                                    ->where(function ($QQQ) use ($year, $quarter) {
                                        return $QQQ
                                            ->where("type", "1")
                                            ->whereHas("invoice", function ($QQQ) use ($year, $quarter) {
                                                return $QQQ
                                                    ->whereYear("created_at", $year);
                                            });
                                    })
                                    ->orWhere(function ($QQQ) use ($year, $quarter) {
                                        return $QQQ
                                            ->where("type", "2")
                                            ->whereYear("date", $year);
                                    });
                            });
                    })
                    ->where("personel_id", $personel?->id)
                    ->get()
                    ->each(function ($order) {
                        $this->feeSharingOriginCalculator($order);
                    });

                for ($i = 1; $i < 5; $i++) {
                    MarketingFee::firstOrCreate([
                        "personel_id" => $personel->id,
                        "year" => $year,
                        "quarter" => $i,
                    ], [
                        "fee_reguler_total" => 0,
                        "fee_reguler_settle" => 0,
                        "fee_target_total" => 0,
                        "fee_target_settle" => 0,
                    ]);
                }

                /* fee reguler */
                $marketing_fee_total = $this->feeMarketingRegulerTotal($personel->id, $year, $quarter);
                $marketing_fee_active = $this->feeMarketingRegulerActive($personel->id, $year, $quarter);
                $marketing_fee_active_pending = $this->feeMarketingRegulerActive($personel->id, $year, $quarter, null, "pending");

                /* fe target */
                $marketing_fee_target_total = $this->feeMarketingTargetTotal($personel->id, $year, $quarter);
                $marketing_fee_target_active = $this->feeMarketingTargetActive($personel->id, $year, $quarter);
                $marketing_fee_target_active_pending = $this->feeMarketingTargetActive($personel->id, $year, $quarter, "pending");

                $marketing_fee = MarketingFee::query()
                    ->where("personel_id", $personel->id)
                    ->where("year", $year)
                    ->whereIn("quarter", $quarter)
                    ->first();

                $old_fee = [
                    "personel_id" => $personel->id,
                    "year" => $year,
                    "quarter" => $quarter,
                    "fee_reguler_total" => $marketing_fee->fee_reguler_total,
                    "fee_reguler_settle" => $marketing_fee->fee_reguler_settle,
                    "fee_target_total" => $marketing_fee->fee_target_total,
                    "fee_target_settle" => $marketing_fee->fee_target_settle,
                ];

                $marketing_fee->fee_reguler_total = $marketing_fee_total;
                $marketing_fee->fee_reguler_settle = $marketing_fee_active;
                $marketing_fee->fee_reguler_settle_pending = $marketing_fee_active_pending;
                $marketing_fee->fee_target_total = $marketing_fee_target_total;
                $marketing_fee->fee_target_settle = $marketing_fee_target_active;
                $marketing_fee->fee_target_settle_pending = $marketing_fee_target_active_pending;
                $marketing_fee->save();

                activity()
                ->causedBy(auth()->id())
                ->performedOn($marketing_fee)
                ->withProperties([
                    "old" => $old_fee,
                    "attributes" => $marketing_fee,
                ])
                ->tap(function (Activity $activity) {
                    $activity->log_name = 'sync';
                })
                ->log('marketing point syncronize');
            });
    }

}