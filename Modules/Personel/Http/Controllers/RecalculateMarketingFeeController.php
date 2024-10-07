<?php

namespace Modules\Personel\Http\Controllers;

use App\Traits\ChildrenList;
use Illuminate\Http\Request;
use App\Traits\ResponseHandler;
use App\Traits\DistributorStock;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Personel\Entities\Personel;
use Spatie\Activitylog\Contracts\Activity;
use Modules\DataAcuan\Entities\FeePosition;
use Modules\Personel\Entities\MarketingFee;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\Personel\Traits\FeeMarketingTrait;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\SalesOrder\Entities\LogWorkerSalesFee;
use Modules\SalesOrder\Entities\FeeSharingSoOrigin;
use Modules\SalesOrderV2\Entities\FeeTargetSharing;
use Modules\SalesOrder\Entities\LogFeeTargetSharing;
use Modules\Personel\Entities\LogMarketingFeeCounter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;
use Modules\Personel\Actions\UpsertFeeTargetSharingAction;
use Modules\Personel\Actions\GetFeeTargetTotalPerQuarterAction;
use Modules\Personel\Actions\GetFeeTargetTotalActivePerQuarterAction;
use Modules\Personel\Actions\GetFeeTargetTotalActivePendingPerQuarterAction;

class RecalculateMarketingFeeController extends Controller
{
    use AuthorizesRequests;
    use FeeMarketingTrait;
    use DistributorStock;
    use ResponseHandler;
    use ChildrenList;

    public function __construct(
        FeeTargetSharingSoOrigin $fee_target_sharing_origin,
        LogMarketingFeeCounter $log_marketing_fee_counter,
        LogFeeTargetSharing $log_fee_target_sharing,
        LogWorkerSalesFee $log_worker_sales_fee,
        FeeSharingSoOrigin $fee_sharing_origin,
        SalesOrderDetail $sales_order_detail,
        FeeTargetSharing $fee_target_sharing,
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
        $this->fee_target_sharing = $fee_target_sharing;
        $this->marketing_fee = $marketing_fee;
        $this->fee_position = $fee_position;
        $this->sales_order = $sales_order;
        $this->personel = $personel;
    }

    public function __invoke(
        $personel_id,
        Request $request,
        UpsertFeeTargetSharingAction $upsert_fee_target_action,
        GetFeeTargetTotalPerQuarterAction $fee_target_total_action,
        GetFeeTargetTotalActivePerQuarterAction $fee_target_total_active_action,
        GetFeeTargetTotalActivePendingPerQuarterAction $fee_target_total_active_pending_action,
    ) {

        // $this->authorize("syncFeeMarketing", $this->marketing_fee);

        /**
         * request validation
         */
        $request->validate([
            "year" => [
                "required",
                "digits:4",
                "integer",
                "min:2000",
                "max:" . (date('Y')),
            ],
            "quarter" => [
                "required",
                "digits:1",
                "integer",
                "min:1",
                "max:4",
            ],
        ], [
            "year.max" => "max year is " . date('Y'),
        ]);

        try {
            $personel = $this->personel->findOrFail($personel_id);

            $fee_product_reference = DB::table('fee_products')
                ->where("year", $request->year)
                ->where("quartal", $request->quarter)
                ->where("type", "1")
                ->get()
                ->pluck("product_id");

            $fee_target_product_reference = DB::table('fee_products')
                ->where("year", $request->year)
                ->where("quartal", $request->quarter)
                ->where("type", "2")
                ->get()
                ->pluck("product_id")
                ->unique()
                ->values();

            /* recalculate fee per product */
            /* fee sharring origin generator */
            /* fee sharing calculator */
            $sales_orders = $this->sales_order->query()
                ->with([
                    "dealer" => function ($QQQ) {
                        return $QQQ->with([
                            "ditributorContract",
                        ]);
                    },
                    "salesOrderDetail" => function ($QQQ) {
                        return $QQQ->with([
                            "salesOrderOrigin",
                            "allSalesOrderOrigin",
                        ]);
                    },
                    "salesCounter",
                    "subDealer",
                    "statusFee",
                    "invoice",
                ])
                ->quartalOrder($request->year, $request->quarter)
                ->where("personel_id", $personel_id)
                ->get()
                ->each(function ($order) use ($fee_product_reference, $fee_target_product_reference) {
                    collect($order->salesOrderDetail)->each(function ($order_detail) use ($fee_product_reference, $fee_target_product_reference) {

                        /* delete fee sharing if fee product was deleted */
                        if (!in_array($order_detail->product_id, $fee_product_reference->toArray())) {
                            $this->fee_sharing_origin->query()
                                ->where("sales_order_detail_id", $order_detail->id)
                                ->forceDelete();
                            }
                    });

                    /* delete fee target sharing if fee product was deleted */
                    $this->fee_target_sharing_origin->query()
                        ->where("sales_order_id", $order->id)
                        ->forceDelete();
                })
                ->each(function ($order) {

                    /* recalculate point per product */
                    $this->feeMarketingPerProductCalculator($order);

                    /* fee sharing generator */
                    $this->feeSharingOriginGenerator($order);

                    /* fee target sharing origin generator */
                    $this->feeTargetSharingOriginGenerator($order);

                    /* recalculte fee in origin */
                    $this->feeSharingOriginCalculator($order);

                });

            /* fee target sharing  */
            $this->feeTargetSharingSpvGenerator($upsert_fee_target_action, $personel_id, $request->year, $request->quarter);

            /* fee reguler */
            $marketing_fee_total = $this->feeMarketingRegulerTotal($personel_id, $request->year, $request->quarter);
            $marketing_fee_active = $this->feeMarketingRegulerActive($personel_id, $request->year, $request->quarter);
            $marketing_fee_active_pending = $this->feeMarketingRegulerActive($personel->id, $request->year, $request->quarter, null, "pending");

            /* fe target */
            $marketing_fee_target_total = $fee_target_total_action(collect($request->all())->merge(["personel_id" => $personel_id])->toArray());
            $marketing_fee_target_active = $fee_target_total_active_action(collect($request->all())->merge(["personel_id" => $personel_id])->toArray());
            $marketing_fee_target_active_pending = $fee_target_total_active_pending_action(collect($request->all())->merge(["personel_id" => $personel_id])->toArray());

            for ($i = 1; $i < 5; $i++) {
                $this->marketing_fee->firstOrCreate([
                    "personel_id" => $request->personel_id,
                    "year" => $request->year,
                    "quarter" => $i,
                ], [
                    "fee_reguler_total" => 0,
                    "fee_reguler_settle" => 0,
                    "fee_target_total" => 0,
                    "fee_target_settle" => 0,
                ]);
            }

            $marketing_fee = $this->marketing_fee->query()
                ->where("personel_id", $request->personel_id)
                ->where("year", $request->year)
                ->where("quarter", $request->quarter)
                ->first();

            $old_fee = [
                "personel_id" => $request->personel_id,
                "year" => $request->year,
                "quarter" => $request->quarter,
                "fee_reguler_total" => $marketing_fee->fee_reguler_total,
                "fee_reguler_settle" => $marketing_fee->fee_reguler_settle,
                "fee_reguler_settle_pending" => $marketing_fee->fee_reguler_settle_pending,
                "fee_target_total" => $marketing_fee->fee_target_total,
                "fee_target_settle" => $marketing_fee->fee_target_settle,
                "fee_target_settle_pending" => $marketing_fee->fee_target_settle_pending,
            ];

            $marketing_fee->fee_reguler_total = $marketing_fee_total;
            $marketing_fee->fee_reguler_settle = $marketing_fee_active;
            $marketing_fee->fee_reguler_settle_pending = $marketing_fee_active_pending;
            $marketing_fee->fee_target_total = $marketing_fee_target_total;
            $marketing_fee->fee_target_settle = $marketing_fee_target_active;
            $marketing_fee->fee_target_settle_pending = $marketing_fee_target_active_pending;
            $marketing_fee->save();

            $test = activity()
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

            return $this->response("00", "success", $marketing_fee);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", [
                "message" => $th->getMessage(),
                "line" => $th->getLine(),
                "file" => $th->getFile(),
                "trace" => $th->getTrace(),
            ], 500);
        }
    }
}
