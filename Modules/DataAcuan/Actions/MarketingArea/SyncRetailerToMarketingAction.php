<?php

namespace Modules\DataAcuan\Actions\MarketingArea;

use Modules\DataAcuan\Actions\GetReatilerAgencyLevelAction;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\KiosDealer\Entities\SubDealer;

class SyncRetailerToMarketingAction
{
    public function execute()
    {
        $retailer_agency_level = new GetReatilerAgencyLevelAction();

        /* sync dealer marketing */
        $dealer = DealerV2::query()
            ->with([
                "areaDistrictDealer",
            ])
            ->whereIn("agency_level_id", $retailer_agency_level()->pluck("id")->toArray())
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
                    $dealer->personel_id = $dealer->areaDistrictDealer->personel_id;
                } else {
                    $dealer->personel_id = null;;
                }
                $dealer->save();
            });

        $sub_dealer = SubDealer::query()
            ->with([
                "areaDistrictDealer",
            ])

            ->where(function ($QQQ) {
                return $QQQ
                    ->whereHas("addressDetail", function ($QQQ) {
                        return $QQQ->where("type", "sub_dealer");
                    })
                    ->orDoesntHave("addressDetail");
            })
            ->lazy()
            ->each(function ($sub_dealer) {
                if ($sub_dealer->areaDistrictDealer) {
                    $sub_dealer->personel_id = $sub_dealer->areaDistrictDealer->personel_id;
                } else {
                    $sub_dealer->personel_id = null;;
                }
                $sub_dealer->save();
            });

        return "all retailer has been synchronized";
    }
}
