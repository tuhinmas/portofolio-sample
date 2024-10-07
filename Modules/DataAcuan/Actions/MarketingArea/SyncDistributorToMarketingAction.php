<?php

namespace Modules\DataAcuan\Actions\MarketingArea;

use Illuminate\Support\Facades\DB;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\DataAcuan\Actions\GetDistributorAgencyLevelAction;

class SyncDistributorToMarketingAction
{
    public function execute()
    {
        $distributor_agency_level = new GetDistributorAgencyLevelAction();

        /* sync dealer marketing */
        $dealer = DealerV2::query()
            ->with([
                "areaDistrictDealer",
                "agencyLevel",
            ])
            ->whereIn("agency_level_id", $distributor_agency_level()->pluck("id")->toArray())
            ->where(function ($QQQ) {
                return $QQQ
                    ->whereHas("addressDetail", function ($QQQ) {
                        return $QQQ->where("type", "dealer");
                    })
                    ->orDoesntHave("addressDetail");
            })
            ->get()
            ->each(function ($dealer) {

                if ($dealer->areaDistrictDealer) {
                    $personel_id = null;
                    if ($dealer->agencyLevel?->name == "D1") {
                        $personel_id = DB::table('marketing_area_regions as mr')
                            ->join("marketing_area_sub_regions as ms", "ms.region_id", "mr.id")
                            ->join("marketing_area_districts as md", "md.sub_region_id", "ms.id")
                            ->where("md.id", $dealer->areaDistrictDealer->id)
                            ->first()?->personel_id;

                    } elseif ($dealer->agencyLevel?->name == "D2") {
                        $personel_id = DB::table('marketing_area_sub_regions as ms')
                            ->join("marketing_area_districts as md", "md.sub_region_id", "ms.id")
                            ->where("md.id", $dealer->areaDistrictDealer->id)
                            ->first()?->personel_id;
                    }
                    
                    $dealer->personel_id = $personel_id;;
                } else {
                    $dealer->personel_id = null;;
                }
                $dealer->save();
            });

        return "all distributor has been synchronized";
    }
}
