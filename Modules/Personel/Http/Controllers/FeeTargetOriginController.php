<?php

namespace Modules\Personel\Http\Controllers;

use App\Traits\ResponseHandlerV2;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DataAcuan\Entities\FeePosition;
use Modules\Personel\Entities\LogMarketingFeeCounter;
use Modules\Personel\Entities\MarketingFee;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Traits\FeeMarketingTrait;
use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;
use Modules\SalesOrder\Entities\LogWorkerSalesFee;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;

class FeeTargetOriginController extends Controller
{
    use ResponseHandlerV2;
    use FeeMarketingTrait;

    public function __construct(
        FeeTargetSharingSoOrigin $fee_target_sharing_origin,
        LogMarketingFeeCounter $log_marketing_fee_counter,
        LogWorkerSalesFee $log_worker_sales_fee,
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
        $this->marketing_fee = $marketing_fee;
        $this->fee_position = $fee_position;
        $this->sales_order = $sales_order;
        $this->personel = $personel;
    }

    public function __invoke(Request $request, $personel_id)
    {
        $year = now()->format("Y");
        $quartal = now()->quarter;

        if ($request->has("year")) {
            $year = $request->year;
        }
        if ($request->quartal) {
            $quartal = $request->quartal;
        }

        try {
            $this->personel->findOrFail($personel_id);
            $fee_position_as_marketing = $this->fee_position->where("fee_as_marketing", true)->first();

            $fee_target_sharing_origins = $this->fee_target_sharing_origin->query()
                ->with([
                    "feeProduct" => function ($QQQ) use ($year) {
                        return $QQQ
                            ->where("year", $year)
                            ->where("type", "2");
                    },
                    "salesOrder" => function ($QQQ) {
                        return $QQQ->with([
                            "invoice",
                        ]);
                    },
                    "product" => function ($QQQ) {
                        return $QQQ->withTrashed();
                    },
                    "statusFee",
                ])
                ->whereHas("statusFee", function ($QQQ) {
                    return $QQQ->withTrashed();
                })
                ->whereHas("feeProduct", function ($QQQ) use ($year) {
                    return $QQQ
                        ->where("year", $year)
                        ->where("type", "2");
                })
                ->where(function ($QQQ) use ($personel_id, $year, $quartal) {
                    return $QQQ
                        ->feeTargetMarketing([$personel_id], $year, $quartal)
                        ->orWhere(function ($QQQ) use ($personel_id, $year, $quartal) {
                            return $QQQ->feeTargetMarketingActive([$personel_id], $year, $quartal);
                        });
                })
                ->get()

            /* follow up doesn't get fee target at all */
                ->groupBy("sales_order_id")
                ->reject(function ($origin) {
                    if ($origin[0]->salesOrder->counter_id) {
                        return $origin;
                    }
                })
                ->flatten()

            /* data mapping */
                ->groupBy([
                    function ($val) {return $val->position_id;},
                    function ($val) {return $val->product_id;},
                    function ($val) {return $val->status_fee_id;},
                ])
                ->map(function ($origin_per_position, $position_id) use ($personel_id, $year, $fee_position_as_marketing) {
                    $origin_per_position = $origin_per_position->map(function ($origin_per_product, $product_id) use ($position_id, $personel_id, $year, $fee_position_as_marketing) {
                        $origin_per_product = $origin_per_product->map(function ($origin_per_fee_status, $status_fee_id) use ($product_id, $position_id, $personel_id, $year, $fee_position_as_marketing) {

                            $fee_product = $origin_per_fee_status[0]->feeProduct->where("year", $year)->where("quantity", "<=", collect($origin_per_fee_status)->sum("quantity_unit"))->sortByDesc("quantity")->first();
                            $status_fee_percentage = $origin_per_fee_status[0]->status_fee_percentage;
                            $detail["data"] = [
                                "handover_status" => $origin_per_fee_status[0]->statusFee->name,
                                "sales" => $origin_per_fee_status->where("salesOrder.type", "2")->sum("salesOrder.total") + $origin_per_fee_status->where("salesOrder.type", "1")->sum("salesOrder.invoice.total"),
                                "settle_sales" => $origin_per_fee_status->where("salesOrder.type", "2")->sum("salesOrder.total") + $origin_per_fee_status->where("salesOrder.type", "1")->where("salesOrder.invoice.payment_status", "settle")->sum("salesOrder.invoice.total"),
                                "target_achievement" => $origin_per_fee_status->sum("quantity_unit"),
                                "fee_per_unit" => $fee_product ? $fee_product->fee : 0,
                                "handover_cut" => ($fee_product ? $fee_product->fee * collect($origin_per_fee_status)->sum("quantity_unit") * $origin_per_fee_status[0]->fee_percentage / 100 : 0.00) - ($fee_product ? $fee_product->fee * collect($origin_per_fee_status)->sum("quantity_unit") * $status_fee_percentage / 100 * $origin_per_fee_status[0]->fee_percentage / 100 : 0.00),
                                "fee_final" => $fee_product ? $fee_product->fee * collect($origin_per_fee_status)->sum("quantity_unit") * $status_fee_percentage / 100 * $origin_per_fee_status[0]->fee_percentage / 100 : 0.00,
                            ];
                            $detail["product"] = $origin_per_fee_status[0]->product;

                            return $detail;
                        });

                        return $origin_per_product->values();
                    });

                    return $origin_per_position;
                })
                ->flatten(2)
                ->groupBy([
                    function ($val) {return $val["product"]->id;},
                    function ($val) {return $val["data"]["handover_status"];},
                ])
                ->map(function ($fee_per_product, $product_id) {
                    $fee_per_product = collect($fee_per_product)->map(function ($fee_per_handover_status, $handover) {
                        $fee_per_handover_status = collect($fee_per_handover_status);
                        $detail["data"] = [
                            "handover_status" => $handover,
                            "sales" => $fee_per_handover_status->sum("data.sales"),
                            "settle_sales" => $fee_per_handover_status->sum("data.settle_sales"),
                            "target_achievement" => $fee_per_handover_status->sum("data.target_achievement"),
                            "fee_per_unit" => $fee_per_handover_status->sum("data.fee_per_unit"),
                            "handover_cut" => $fee_per_handover_status->sum("data.handover_cut"),
                            "fee_final" => $fee_per_handover_status->sum("data.fee_final"),
                        ];
                        $detail["product"] = $fee_per_handover_status[0]["product"];

                        return $detail;
                    });

                    return $fee_per_product;
                });

            return $this->response("00", "succes", $fee_target_sharing_origins);

        } catch (\Throwable$th) {
            return $this->response("01", "failed", $th, 500);
        }
    }
}
