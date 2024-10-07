<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;

/**
 * change marketing if store address chnges
 * marketing base on new dealer address
 */
trait ChangeMarketingStore
{
    public function updateMarketing($district_id)
    {
        $personel_id = null;
        $district = DB::table('marketing_area_districts')
            ->whereNull("deleted_at")
            ->where("district_id", $district_id)
            ->first();

        /* if there has no area */
        if ($district) {
            $personel_id = MarketingAreaDistrict::query()
                ->whereNull("deleted_at")
                ->where("district_id", $district_id)
                ->value("personel_id");
        }
        return $personel_id;
    }

    /**
     * agency level check on distributor
     *
     * @param [type] $agency_level_id
     * @return void
     */
    public function checkAgencyLevelDistributor($dealer_id)
    {
        $agency_level = DB::table('dealers as d')
            ->join("agency_levels as ag", "ag.id", "d.agency_level_id")
            ->whereNull("d.deleted_at")
            ->whereNull("ag.deleted_at")
            ->where("d.id", $dealer_id)
            ->select("ag.name")
            ->first();

        return $agency_level?->name;
    }

    /**
     * distributor changed address
     */
    public function distributorMarketingChanges($dealer_id, $agency_level, $district_id)
    {
        $personel_id = null;
        $district = MarketingAreaDistrict::query()
            ->with([
                "subRegionWithRegion",
            ])
            ->where("district_id", $district_id)
            ->first();

        /* check contract */
        $contract = DB::table('distributor_contracts')
            ->whereNull("deleted_at")
            ->where("dealer_id", $dealer_id)
            ->first();

        if ($district) {
            $personel_id = $district->personel_id;

            /* distributor D1 */
            if ($agency_level == "D1" && $contract) {
                $personel_id = $district->subRegionWithRegion->region->personel_id;
            }

            /* distributor D2 */
            if ($agency_level == "D2" && $contract) {
                $personel_id = $district->subRegionWithRegion->personel_id;
            }

            $dealer = DB::table('dealers')
                ->where("id", $dealer_id)
                ->update([
                    "personel_id" => $personel_id,
                ]);
        }
    }
}
