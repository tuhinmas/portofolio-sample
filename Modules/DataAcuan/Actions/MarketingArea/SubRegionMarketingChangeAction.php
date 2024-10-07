<?php

namespace Modules\DataAcuan\Actions\MarketingArea;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\DataAcuan\Entities\SubRegion;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\Personel\Entities\Personel;

class SubRegionMarketingChangeAction
{
    public function __invoke(SubRegion $sub_region, string $old_marketing_id)
    {
        /**
         * marketing sub region (RMC example) changes will
         * 1. district handled by old_marketing will tak over
         * 2. retailer handled by old marketing will take over
         * 3. distributor D2 handled by old marketing will take over
         * 4. distributor D1 handled by old marketing, if new marketing is MDM will take over
         *
         */
        if ($sub_region->personel_id == $old_marketing_id) {
            return 0;
        }

        /* district take over */
        $districts = self::districtTakeOver($sub_region, $old_marketing_id);

        /* retailer / distributor take over */
        self::retailerAndDistributorTakeOver($sub_region, $districts->pluck("district_id")->toArray(), $old_marketing_id);

        /* supervisor update and district take over in special condition  */
        self::personelUpdateSupervisor($sub_region, $districts);
    }

    /**
     * district will take over by new marketing
     *
     * @param SubRegion $sub_region
     * @param [type] $old_marketing_id
     * @return void
     */
    public static function districtTakeOver(SubRegion $sub_region, $old_marketing_id)
    {
        return MarketingAreaDistrict::query()
            ->with([
                "personel",
            ])
            ->where("sub_region_id", $sub_region->id)
            ->get()
            ->each(function ($district) use ($sub_region, $old_marketing_id) {

                /** area district take over */
                if ($district->personel_id == $old_marketing_id) {
                    $district->personel_id = $sub_region->personel_id;
                    $district->applicator_id = null;
                    $district->save();
                    $district->refresh();
                }
            });
    }

    /**
     * retailer in sub region area handled by old marketing
     * will take over by new marketing
     *
     * @param SubRegion $sub_region
     * @param array $districts
     * @param string $old_marketing_id
     * @return void
     */
    public static function retailerAndDistributorTakeOver(SubRegion $sub_region, array $districts, string $old_marketing_id)
    {
        $is_rmc = DB::table('positions as po')
            ->join("personels as p", "p.position_id", "po.id")
            ->where("p.id", $sub_region->personel_id)
            ->whereIn("po.name", position_rmc())
            ->select("po.id")
            ->first();

        /* dealer in district area */
        $dealer_in_district = DB::table('dealers as d')
            ->join("address_with_details as ad", "ad.parent_id", "d.id")
            ->whereNull("ad.deleted_at")
            ->whereNull("d.deleted_at")
            ->where("d.personel_id", $old_marketing_id)
            ->whereIn("ad.district_id", $districts)
            ->where("ad.type", "dealer")
            ->select("d.id")
            ->get()
            ->pluck("id")
            ->toArray();

        /* sub dealer in district area */
        $sub_dealer_in_district = DB::table('sub_dealers as d')
            ->join("address_with_details as ad", "ad.parent_id", "d.id")
            ->whereNull("ad.deleted_at")
            ->whereNull("d.deleted_at")
            ->where("d.personel_id", $old_marketing_id)
            ->whereIn("ad.district_id", $districts)
            ->where("ad.type", "sub_dealer")
            ->select("d.id")
            ->get()
            ->pluck("id")
            ->toArray();

        /**
         * dealer retailer (R1, R2, R3) and distributor(D1, D2) take take over
         */
        Dealer::query()
            ->whereIn("id", $dealer_in_district)
            ->where("personel_id", $old_marketing_id)

        /* new marketing is RMC */
            ->when($is_rmc, function ($QQQ) {
                return $QQQ
                    ->whereHas("agencyLevel", function ($QQQ) {
                        return $QQQ->whereNotIn("name", agency_level_D1());
                    });

            })
            ->get()
            ->each(function ($dealer) use ($sub_region) {
                $dealer->personel_id = $sub_region->personel_id;
                $dealer->save();
            });

        /**
         * sub dealer retailer (R1, R2, R3)take take over
         */
        SubDealer::query()
            ->whereIn("id", $sub_dealer_in_district)
            ->where("personel_id", $old_marketing_id)
            ->get()
            ->each(function ($ub_dealer) use ($sub_region) {
                $ub_dealer->personel_id = $sub_region->personel_id;
                $ub_dealer->save();
            });
    }

    /**
     * supervisor update
     *
     * @param SubRegion $sub_region
     * @return void
     */
    public static function personelUpdateSupervisor(SubRegion $sub_region, Collection $district_in_sub_regions)
    {

        /* get region detail */
        $region = DB::table('marketing_area_regions')
            ->whereNull("deleted_at")
            ->where("id", $sub_region->region_id)
            ->first();

        /* compare personel in sub region and region */
        if ($sub_region->personel_id !== $region->personel_id) {

            /**
             * if personel in sub region is not same with personel in region
             * update supervisor personel sub region with personel in region
             */
            $personel = Personel::findOrFail($sub_region->personel_id);
            $personel->supervisor_id = $region->personel_id;
            $personel->save();
        }

        /**
         * if in this sub region there is marketing has more than one area
         * in another sub region, all area which handled by its marketing
         * will be revoked from all area in this sub region, and these
         * area will handled by new RMC
         */

        /* marketing area exclude RMC */
        $marketing_area_exclude_rmc = $district_in_sub_regions
            ->reject(function ($district) use ($sub_region) {
                return $district->personel_id == $sub_region->personel_id;
            })
            ->pluck("personel_id")
            ->toArray();

        /**
         * district in another sub region which handled by marketing
         * has more than two area in diffrent sub region and
         * diffrent RMC
         */
        $district_list_in_another_sub_region = MarketingAreaDistrict::query()
            ->whereIn("personel_id", $marketing_area_exclude_rmc)
            ->whereHas("subRegionWithRegion", function ($QQQ) use ($sub_region) {
                return $QQQ->where("personel_id", "!=", $sub_region->personel_id);
            })
            ->whereHas("personel", function ($QQQ) use ($sub_region) {
                return $QQQ->where("supervisor_id", "!=", $sub_region->personel_id);
            })
            ->where("sub_region_id", "!=", $sub_region->id)
            ->get();

        if ($district_list_in_another_sub_region->count() > 0) {

            /* update marketing district with rmc because of the reason already mentioned */
            MarketingAreaDistrict::query()
                ->whereIn("personel_id", $district_list_in_another_sub_region->pluck("personel_id")->toArray())
                ->where("sub_region_id", $sub_region->id)
                ->get()
                ->each(function ($district) use ($sub_region) {
                    $district->personel_id = $sub_region->personel_id;
                    $district->save();
                });
        }

        /* update all personel under subregion with new supervisor */
        $district_in_sub_regions
            ->pluck("personel")
            ->filter()
            ->unique("id")
            ->reject(function ($marketing) use ($district_list_in_another_sub_region) {
                return in_array($marketing->id, $district_list_in_another_sub_region->pluck("personel_id")->toArray());
            })
            ->reject(function ($marketing) use ($sub_region) {
                return $marketing->id == $sub_region->personel_id;
            })
            ->each(function ($marketing) use ($sub_region) {
                $marketing->supervisor_id = $sub_region->personel_id;
                $marketing->save();
            });
    }
}
