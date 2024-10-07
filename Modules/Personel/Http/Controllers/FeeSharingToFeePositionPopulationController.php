<?php

namespace Modules\Personel\Http\Controllers;

use App\Traits\ResponseHandlerV2;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DataAcuan\Entities\FeePosition;
use Modules\SalesOrder\Entities\FeeSharingSoOrigin;

class FeeSharingToFeePositionPopulationController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(
        FeeSharingSoOrigin $fee_sharing_origin,
        FeePosition $fee_position,
    ) {
        $this->fee_sharing_origin = $fee_sharing_origin;
        $this->fee_position = $fee_position;
    }

    public function __invoke(Request $request, $sales_order_id)
    {
        try {
            $fee_sharing_origins = $this->fee_sharing_origin->query()
                ->with([
                    "position",
                    "personel" => function ($QQQ) {
                        return $QQQ->with([
                            "position",
                        ]);
                    },
                ])
                ->where("sales_order_id", $sales_order_id)
                ->get();

            $is_follow_up = $fee_sharing_origins->where("fee_status", "sales counter")->first();
            $is_hand_over = $fee_sharing_origins->where("handover_status", true)->first();
            $purchaser = $fee_sharing_origins->where("fee_status", "purchaser")->first();
            $fee_reduction_sc_percentage = $fee_sharing_origins->where("sc_reduction_percentage", "!=", null)->first()?->sc_reduction_percentage;
            $position_fee_reduction = $fee_sharing_origins->where("sc_reduction_percentage", "!=", null)->pluck("position.name")->unique()->values();

            $fee_per_position = $fee_sharing_origins
                ->reject(function ($origin) use ($is_follow_up) {
                    if ($is_follow_up) {
                        return $origin->fee_status == $is_follow_up->fee_status;
                    }
                })
                ->sortBy("fee_percentage")
                ->groupBy("position_id")
                ->map(function ($origin_per_position, $position_id) use ($is_follow_up, $is_hand_over, $purchaser, $fee_reduction_sc_percentage, $position_fee_reduction) {

                    /* fee percentage */
                    $fee_percentage = $origin_per_position->first()->fee_percentage;
                    if ($is_hand_over && $position_id == $purchaser->position_id) {
                        $fee_percentage = $origin_per_position->where("fee_status", "purchaser")->first()->fee_percentage;
                    }

                    /* fee nominal before cut */
                    $fee_nominal_brfore_cut = $origin_per_position->unique("sales_order_detail_id")->sum("total_fee") * $origin_per_position->first()->fee_percentage / 100;
                    if ($is_hand_over && $position_id == $purchaser->position_id) {
                        $fee_nominal_brfore_cut = $origin_per_position->unique("sales_order_detail_id")->sum("total_fee") * $origin_per_position->where("fee_status", "purchaser")->first()->fee_percentage / 100;
                    }

                    /* fee_reduction_sc */
                    $fee_reduction_sc = $origin_per_position->first()->sc_reduction_percentage ? $fee_nominal_brfore_cut * $origin_per_position->first()->sc_reduction_percentage / 100 : 0;
                    if ($is_hand_over && $position_id == $purchaser->position_id) {
                        $fee_reduction_sc = $origin_per_position->where("fee_status", "purchaser")->first()->sc_reduction_percentage ? $fee_nominal_brfore_cut * $origin_per_position->where("fee_status", "purchaser")->first()->sc_reduction_percentage / 100 : 0;
                    }

                    /* fee_final */
                    $fee_final = $origin_per_position->unique("sales_order_detail_id")->sum("fee_shared");
                    if ($origin_per_position->where("fee_status", "purchaser")->first() && $is_hand_over) {
                        $fee_final = $origin_per_position->where("handover_status", true)->sum("fee_shared");
                    }

                    $detail["position_name"] = $origin_per_position->first()->position->name;
                    $detail["personel_id"] = $origin_per_position->first()->personel_id;
                    $detail["personel_name"] = $origin_per_position->first()->personel?->name;
                    $detail["fee_percentage"] = $fee_percentage;
                    $detail["fee_nominal_before_cut"] = $fee_nominal_brfore_cut;
                    $detail["fee_reduction_sc"] = $fee_reduction_sc;
                    $detail["fee_handover_percentage"] = $origin_per_position->where("fee_status", "purchaser")->first() && $is_hand_over ? $is_hand_over->fee_percentage : 0;
                    $detail["fee_final"] = $fee_final;

                    $data["additional_data"] = [
                        "total_fee" => $origin_per_position->unique("sales_order_detail_id")->sum("total_fee"),
                        "fee_reduction_sc" => $fee_reduction_sc_percentage,
                        "position_fee_reduction" => $position_fee_reduction,
                    ];
                    $data["data"] = $detail;
                    return $data;
                });

            if ($is_follow_up) {

                $fee_sc_origin = $fee_sharing_origins
                    ->filter(function ($origin) use ($is_follow_up) {
                        if ($is_follow_up) {
                            return $origin->fee_status == $is_follow_up->fee_status;
                        }
                    });

                $fee_per_position[$fee_sc_origin->first()->personel->position->id] = [
                    "additional_data" => [
                        "total_fee" => $fee_sc_origin->unique("sales_order_detail_id")->sum("total_fee"),
                        "fee_reduction_sc" => $fee_reduction_sc_percentage,
                        "position_fee_reduction" => $position_fee_reduction,
                    ],
                    "data" => [
                        "position_name" => $fee_sc_origin->first() ? $fee_sc_origin->first()->personel->position->name : null,
                        "personel_name" => $fee_sc_origin->first() ? $fee_sc_origin->first()->personel->name : null,
                        "fee_percentage" => null,
                        "fee_nominal_before_cut" => null,
                        "fee_reduction_sc" => $fee_sc_origin->first() ? $fee_sc_origin->first()->fee_shared : null,
                        "fee_handover_percentage" => null,
                        "fee_final" => $fee_sc_origin->first() ? $fee_sc_origin->first()->fee_shared : null,
                    ],
                ];
            }

            if ($request->has("personel_id")) {
                $fee_per_position = $fee_per_position
                    ->filter(function ($fee, $position_id) use ($request) {
                        return $fee["data"]["personel_id"] == $request->personel_id;
                    });
            }

            return $this->response("00", "success", $fee_per_position);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th);
        }
    }
}
