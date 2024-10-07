<?php

namespace Modules\DataAcuan\Repositories;

use App\Traits\ChildrenList;
use App\Traits\DistributorStock;
use Illuminate\Console\Command;
use Modules\Personel\Traits\FeeMarketingTraitV2;
use Modules\SalesOrderV2\Entities\FeeTargetSharingOrigin;
use Spatie\Activitylog\Contracts\Activity;
use Illuminate\Support\Facades\DB;
use Modules\SalesOrder\Entities\FeeSharingSoOrigin;
use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;
use Modules\SalesOrder\Traits\SalesOrderTrait;
use Modules\SalesOrder\Entities\LogFeeTargetSharing;
use Modules\SalesOrder\Entities\LogWorkerSalesFee;
use Modules\DataAcuan\Entities\FeePosition;
use Modules\Personel\Entities\LogMarketingFeeCounter;
use Modules\Personel\Entities\MarketingFee;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Traits\FeeMarketingTrait;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;

class FeeProductRepository {

    protected $fee_position,
        $log_marketing_fee_counter, 
        $log_fee_target_sharing, 
        $log_worker_sales_fee,
        $fee_sharing_origin,
        $sales_order_detail,
        $marketing_fee,
        $sales_order,
        $fee_target_sharing_origin,
        $personel;

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
        Personel $personel
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
    
    // use FeeMarketingTraitV2;
    use FeeMarketingTrait;
    use DistributorStock;
    use ChildrenList;

    public function syncFeeByProduct($year, $quarter, $productId, $byPersonel = true)
    {
        $this->deleteSharing($year, $quarter);
        $referenceFeeProduct = $this->getFeeProduct($year, $quarter);

        Personel::whereHas("position", function ($QQQ) {
            return $QQQ->whereIn("name", marketing_positions());
        })->when($byPersonel, function($q) use($productId){
            $q->whereIn("id", $this->findPersonelRelateProduct($productId));
        })->when(!$byPersonel, function($q){
            $q->orWhereHas("salesOrder")->orWhereHas("marketingFee");
        })
        ->orderBy("name")
        ->get()
        ->each(function ($personel) use ($year, $quarter, $referenceFeeProduct, $productId) {
            $this->feeSharingFeeGenerator($personel, $year, $quarter, $referenceFeeProduct, $productId);
            $this->regenerateFeeMarketing($personel, $year, $quarter, $productId);
        });
    }

    public function feeSharingFeeGenerator($personel, $year, $quarter, $referenceFeeProduct, $productId)
    {
        $feeProductFeeference = $referenceFeeProduct['fee_product'];
        $feeProductFeeTargetFeference = $referenceFeeProduct['fee_product_target'];
        
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
            ->whereHas('salesOrderDetail', function($q) use($productId){
                $q->where('product_id', $productId);
            })
            ->feeMarketing($year, $quarter)
            ->where("personel_id", $personel->id)
            ->get()
            ->each(function ($order) use ($feeProductFeeference, $feeProductFeeTargetFeference, $productId) {
                collect($order->salesOrderDetail->where('product_id', $productId))->each(function ($order_detail) use ($feeProductFeeference, $feeProductFeeTargetFeference) {
                    if (!in_array($order_detail->product_id, $feeProductFeeference->toArray())) {
                        FeeSharingSoOrigin::where("sales_order_detail_id", $order_detail->id)->delete();
                    }

                    if (!in_array($order_detail->product_id, $feeProductFeeTargetFeference->toArray())) {
                        FeeTargetSharingOrigin::where("sales_order_detail_id", $order_detail->id)->delete();
                    }
                });
                $this->feeMarketingPerProductCalculator($order, $productId);
                $this->feeSharingOriginGenerator($order);
                $this->feeTargetSharingOriginGenerator($order);
            });
    }

    public function regenerateFeeMarketing($personel, $year, $quarter, $productId)
    {
        SalesOrder::with([
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
        ->feeMarketing($year, $quarter)
        ->where("personel_id", $personel->id)
        ->whereHas('salesOrderDetail', function($q) use($productId){
            $q->where('product_id', $productId);
        })
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
            ->where("quarter", $quarter)
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
    }

    public function findPersonelRelateProduct($productId)
    {
        return SalesOrder::join('sales_order_details', function($q){
                $q->on('sales_orders.id','sales_order_details.sales_order_id')->whereNull('sales_order_details.deleted_at');
            })
            ->where('sales_order_details.product_id', $productId)
            ->groupBy('sales_orders.personel_id')
            ->select('personel_id')
            ->get()
            ->pluck('personel_id')
            ->toArray();
    }

    private function deleteSharing($year, $quarter)
    {
        FeeSharingSoOrigin::query()
        ->whereYear("confirmed_at", $year)
        ->whereRaw("quarter(confirmed_at) = ?", $quarter)
        ->delete();

        FeeTargetSharingOrigin::query()
            ->whereYear("confirmed_at", $year)
            ->whereRaw("quarter(confirmed_at) = ?", $quarter)
            ->delete();
    }

    private function getFeeProduct($year, $quarter)
    {
        $feeProductReference = DB::table('fee_products')
            ->where("year", $year)
            ->where("quartal", $quarter)
            ->where("type", "1")
            ->get()
            ->pluck("product_id");

        $feeTargetProductReference = DB::table('fee_products')
            ->where("year", $year)
            ->where("quartal", $quarter)
            ->where("type", "2")
            ->get()
            ->pluck("product_id")
            ->unique()
            ->values();

        return [
            'fee_product' => $feeProductReference,
            'fee_product_target' => $feeTargetProductReference
        ];
    }

    public function deleteExistJobFeeProduct($year, $quarter, $productId)
    {
        $jobs = DB::table('jobs as t')
            ->select('*')
            ->selectRaw("REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(payload, 'year', -1), ';', 2), ':', -1), '\"', '') AS yearFieldName")
            ->selectRaw("REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(payload, 'quarter', -1), ';', 2), ':', -1), '\"', '') AS quarterFieldName")
            ->selectRaw("REPLACE(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(payload, 'productId', -1), ';', 2), ':', -1), '\"', ''), '\\\\', '') AS productFieldName")
            ->having('yearFieldName', '=', $year)
            ->having('quarterFieldName', '=', $quarter)
            ->having('productFieldName', '=', $productId)
            ->get()->pluck('id')->toArray();

        if (isset($jobs)) {
            DB::table('jobs')->whereIn('id', $jobs)->delete();
        }
    }

}