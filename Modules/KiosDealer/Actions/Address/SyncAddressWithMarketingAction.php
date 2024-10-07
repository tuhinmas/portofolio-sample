<?php

namespace Modules\KiosDealer\Actions\Address;

use Illuminate\Support\Facades\DB;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\KiosDealer\Entities\Dealer;

class SyncAddressWithMarketingAction
{

    public function __invoke($dealer, string $district_id)
    {
        $area = DB::table('marketing_area_districts as md')
            ->join("marketing_area_sub_regions as ms", "ms.id", "md.sub_region_id")
            ->join("marketing_area_regions as mr", "mr.id", "ms.region_id")
            ->whereNull("md.deleted_at")
            ->whereNull("ms.deleted_at")
            ->whereNull("mr.deleted_at")
            ->where("md.district_id", $district_id)
            ->select("md.*", "ms.personel_id as marketing_sub_region", "mr.personel_id as marketing_region")
            ->first();

        if ($area) {
            $personel_id = $area->personel_id;

            switch (true) {
                case $dealer instanceof Dealer || $dealer instanceof DealerV2:
                    $dealer->load(["distributorContractActive"]);
                    if ($dealer->distributorContractActive) {
                        $D1 = DB::table('agency_levels')->whereNull("deleted_at")->whereIn("name", agency_level_D1())->first();
                        $D2 = DB::table('agency_levels')->whereNull("deleted_at")->whereIn("name", agency_level_D2())->first();

                        $personel_id = match ($dealer->distributorContractActive->distributor_level) {
                            $D1->id => $area->marketing_region,
                            $D2->id => $area->marketing_sub_region,
                            default => $area->personel_id
                        };

                    }
                    break;

                default:
                    break;
            }

            $dealer->personel_id = $personel_id;
            $dealer->save();
        }
        return $dealer;

    }
}
