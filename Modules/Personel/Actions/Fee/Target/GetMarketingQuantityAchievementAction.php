<?php

namespace Modules\Personel\Actions\Fee\Target;

use Illuminate\Support\Facades\DB;
use Modules\SalesOrderV2\ClassHelper\FeeTargetNomialSharingMapper;
use Modules\SalesOrder\Actions\FeeTargetSharingOrigin\GetFeeTargetSharingSoOriginByPersonelAction;
use Modules\SalesOrder\Actions\GetFeeTargetSharingSoOriginByMarketingAction;

class GetMarketingQuantityAchievementAction
{
    /**
     * payload include
     * @param [type] $personel_id
     * @param [type] $year
     * @param [type] $quarter
     * @return void
     */
    public function __invoke(array $payload)
    {

        /*
        |======================================================================================================
        | FEE TARGET NOMINAL SHARING RULE
        |==================================================================================================
        | 1. fee total is total sales from confirmed order (confirmed, pending, returned)
        | 2. fee active is according payment time for direct sales, and direct origin for indirect sales
        | 3. supervisor will get fee if marketing reach target
        | 4. reach target is according marketing per position per product per status fee handover
        | 3. return order is not considered as fee active
        | 4. fee pending is include confirmed
        | 5. order from follow up considered to get fee
        |--------------------------------------------------------------------------------------------------
         */

        extract($payload);

        $fee_target_sharing_origins_personel = new GetFeeTargetSharingSoOriginByPersonelAction();
        $fee_target_sharing_origins_marketing = new GetFeeTargetSharingSoOriginByMarketingAction();
        $fee_target_nominal_sharing_mapper = new FeeTargetNomialSharingMapper();

        $purchaser_position = DB::table('fee_positions')
            ->whereNull("deleted_at")
            ->where("fee_as_marketing", true)
            ->first();

        $marketing_get_fee = collect();
        $fee_target_sharing_personel = $fee_target_sharing_origins_personel($payload)
            ->pluck("marketing_id")
            ->unique()
            ->values()
            ->each(function ($marketing_id) use (
                &$fee_target_sharing_origins_marketing,
                $fee_target_nominal_sharing_mapper,
                &$marketing_get_fee,
                $purchaser_position,
                $personel_id,
                &$payload,
                $quarter,
                $year,
            ) {

                /*
            |----------------------------------------------------
            | Get marketing subordinat or as marketing
            | achievement during quarter
            |---------------------------------------------
             */
                $payload["personel_id"] = $marketing_id;
                $fee_target_sharings = $fee_target_sharing_origins_marketing($payload);

                $fee_target_origin_as_marketing = $fee_target_sharings
                    ->filter(function ($origin) use ($purchaser_position) {
                        if ($purchaser_position) {
                            return $origin->position_id == $purchaser_position->position_id;
                        }
                    });

                if ($fee_target_origin_as_marketing->count() <= 0) {
                    return true;
                }

                $payload["all_achievement"] = true;
                $marketing_sales_reach_target = $fee_target_nominal_sharing_mapper($payload, $fee_target_origin_as_marketing);

                /**
             * Supervisor will calculate to get fee target for this product
             * if marketing reach target in this quarter for this product
             */
                $spv_get_fee = collect();
                $supervisor_target = $fee_target_sharings
                    ->where("marketing_id", $marketing_id)
                    ->where("position_id", "!=", $purchaser_position->position_id)
                    ->whereIn("product_id", $marketing_sales_reach_target->filter(fn($fee) => $fee["is_reach_target"])->pluck("product_id"))
                    ->groupBy([
                        function ($val) {return $val->personel_id;},
                    ])
                    ->map(function ($fee_per_personel, $personel_id) use (
                        $fee_target_nominal_sharing_mapper,
                        $marketing_sales_reach_target,
                        $fee_target_sharings,
                        $purchaser_position,
                        &$marketing_get_fee,
                        &$spv_get_fee,
                        $payload,
                    ) {

                        $payload["personel_id"] = $personel_id;

                        $fee_target_sharings = $fee_target_sharings->filter(fn($origin) => $origin->personel_id == $personel_id);
                        $fee = $fee_target_nominal_sharing_mapper($payload, $fee_target_sharings, $marketing_sales_reach_target)
                            ->filter(fn($fee) => $fee["is_reach_target"]);

                        $spv_get_fee->push($fee);

                        return $fee_per_personel;
                    });

                $marketing_get_fee
                    ->push($marketing_sales_reach_target)
                    ->push($spv_get_fee->flatten(1));
            })
            ->values();

        return (object) [
            "marketing_list" => $fee_target_sharing_personel,
            "marketing_get_fee" => $marketing_get_fee->flatten(1),
        ];
    }
}
