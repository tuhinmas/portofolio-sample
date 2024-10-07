<?php

namespace Modules\Personel\Http\Controllers\Fee;

use Illuminate\Http\Request;
use App\Traits\ResponseHandlerV2;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Personel\Entities\Personel;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrderV2\Entities\FeeTargetSharing;
use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;
use Modules\SalesOrderV2\ClassHelper\FeeTargetNomialSharingMapper;
use Modules\SalesOrderV2\Actions\GetFeeProductTargetReferenceByQuarterAction;
use Modules\Personel\Actions\Fee\Target\GetMarketingQuantityAchievementAction;

class MarketingFeeTargetTrackerController extends Controller
{
    use ResponseHandlerV2;

    public function __construct(
        Personel $personel,
        SalesOrder $sales_order,
        FeeTargetSharing $fee_target_sharing,
        FeeTargetSharingSoOrigin $fee_target_sharing_origin,
    ) {
        $this->personel = $personel;
        $this->sales_order = $sales_order;
        $this->fee_target_sharing = $fee_target_sharing;
        $this->fee_target_sharing_origin = $fee_target_sharing_origin;
    }

    /**
     * fee tracker
     *
     * @param Request $request
     * request include personel_id
     * @return void
     */
    public function __invoke(
        Request $request,
        FeeTargetNomialSharingMapper $fee_target_nominal_sharing_mapper,
        GetFeeProductTargetReferenceByQuarterAction $fee_target_reference,
        GetMarketingQuantityAchievementAction $fee_target_achievement,
    ) {
        ini_set('max_execution_time', '900');

        $request->validate([
            "personel_id" => [
                "required",
            ],
            "year" => [
                "required",
            ],
            "quarter" => [
                "required",
            ],
        ]);

        try {
            $personel = $this->personel->query()
                ->with([
                    "position",
                    "supervisor",
                ])
                ->findOrFail($request->personel_id);

            /* position purchaser */
            $purchaser_position = DB::table('fee_positions')
                ->whereNull("deleted_at")
                ->where("fee_as_marketing", true)
                ->first();

            $achievement_marketing = $fee_target_achievement($request->all());

            return [
                "subordinat_marketing" => $achievement_marketing
                    ->marketing_list
                    ->reject(fn($marketing_id) => $marketing_id == $request->personel_id)
                    ->values(),

                "marketing_achievement" => $achievement_marketing
                    ->marketing_get_fee
                    ->sortBy("product_id")
                    ->filter(fn($fee) => in_array($fee["personel_id"], $achievement_marketing->marketing_list->toArray()))
                    ->values()
                    ->groupBy([
                        fn($origin) => $origin["position_id"],
                        fn($origin) => $origin["personel_id"],
                        fn($origin) => $origin["product_id"],
                        fn($origin) => $origin["status_fee_id"],
                    ]),

                "achievement_this_marketing" => $achievement_marketing
                    ->marketing_get_fee
                    ->sortBy("product_id")
                    ->filter(fn($fee) => in_array($fee["personel_id"], $achievement_marketing->marketing_list->toArray()))
                    ->filter(fn($fee) => $fee["is_reach_target"])
                    ->values()
                    ->groupBy([
                        fn($origin) => $origin["position_id"],
                        fn($origin) => $origin["personel_id"],
                        fn($origin) => $origin["product_id"],
                        fn($origin) => $origin["status_fee_id"],
                    ]),
            ];
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th, 500);
        }
    }
}
