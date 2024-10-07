<?php

namespace Modules\Personel\Actions\Marketing;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\DataAcuan\Entities\Region;
use Modules\DataAcuan\Entities\SubRegion;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\Personel\Entities\Personel;

class MarketingSubRegionSupervisorChangeAction
{

    /**
     * marketing in district supervisor changes rules, marketing will revoke from district
     * that not inside sub region handled by his supervisor or by him self and set to.
     * marketing sub region. applicator on revoked area will set to null. no
     * assignment to new area, revoke only
     *
     * @param Personel $personel
     * @return void
     */
    public function __invoke(Personel $personel)
    {
        /**
         * sub region handle by marketing
         */
        $area_sub_region = DB::table('marketing_area_sub_regions')
            ->whereNull("deleted_at")
            ->where("personel_id", $personel->id)
            ->get();

        if ($area_sub_region->count() <= 0) {
            return;
        }

        /**
         * this action is only for RMC and MDM, in condition marketing sub region
         * is MM and supervisor change (current doesn't have spv) than we need
         * to discuss for detail
         */
        $mdm_position = DB::table('positions')
            ->whereIn("name", position_mdm())
            ->first();

        $rmc_position = DB::table('positions')
            ->whereIn("name", position_rmc())
            ->first();

        $mm_position = DB::table('positions')
            ->whereIn("name", position_mm())
            ->first();

        $spv_is_active_mm = DB::table('personels')
            ->where("id", $personel->supervisor_id)
            ->when($mm_position, function ($QQQ) use ($mm_position) {
                return $QQQ->where("position_id", $mm_position->id);
            })
            ->where("status", 1)
            ->first();

        /**
         * marketing supervisor is marketing manager
         */
        if ($spv_is_active_mm) {
            switch ($personel->position_id) {

                /**
                 * current marketing nis MDM and current marketing region is inactive MM
                 * 1. region will set to MDM
                 * 2. Distributor D1 takeover to MDM
                 */
                case $mdm_position->id:
                    $region = self::regionTakeOver($area_sub_region, $personel, $spv_is_active_mm, "mdm");                    
                    self::marketingRegionSupervisorUpdate($region, $personel, $spv_is_active_mm);
                    self::distributorD1TakeOver($area_sub_region, $personel->id);
                    break;

                /**
                 * current marketing is RMC and current marketing region is inactive MM
                 * 1. region will set to active MM
                 * 2. Distributor D1 takeover to active MM
                 */
                case $rmc_position->id:
                    self::regionTakeOver($area_sub_region, $personel, $spv_is_active_mm);
                    self::marketingSubRegionSupervisorUpdate($area_sub_region, $personel, $spv_is_active_mm);
                    self::distributorD1TakeOver($area_sub_region, $spv_is_active_mm->id);
                    break;

                default:
                    break;
            }

            // dd([
            //     $spv_is_active_mm->id,
            //     $area_sub_region->pluck("region_id")->unique()->toArray(),
            // ]);

            return;
        } elseif (!in_array($personel->position_id, [$mdm_position?->id, $rmc_position?->id])) {
            return;
        }

        /**
         * region that not handled by his supervisor
         * or him self, but sub region in it
         */
        $unmatch_region = DB::table('marketing_area_regions')
            ->whereNull("deleted_at")
            ->whereIn("id", $area_sub_region->pluck("region_id")->unique()->toArray())
            ->where("personel_id", "!=", $personel->supervisor_id)
            ->where("personel_id", "!=", $personel->id)
            ->select("*")
            ->get();

        if ($unmatch_region->count() > 0) {
            $unmatch_sub_regions = self::subRegionTakeOver($unmatch_region, $personel);
            self::districtTakeOver($unmatch_sub_regions, $personel);
            self::retailerTakeOver($unmatch_sub_regions, $personel);
        }
    }

    /**
     * in case new supervisor is active Marketing Manager
     * and current marketing is MDM, region will take
     * over by MDM, if current marketing is RMCM
     * region will take over by active MM
     *
     * @param [type] $spv_is_active_mm
     * @param Collection $area_sub_region
     * @param Personel $personel
     * @return void
     */
    public static function regionTakeOver(Collection $area_sub_region, Personel $personel, $spv_is_active_mm, $current_marketing_level = "rmc")
    {
        /* region take over by active MM */
        return Region::query()
            ->get()
            ->each(function ($region) use ($spv_is_active_mm, $area_sub_region, $personel, $current_marketing_level) {
                if (
                    in_array($region->id, $area_sub_region->pluck("region_id")->unique()->toArray())
                    && $region->personel_id != $personel->supervisor_id
                    && $region->personel_id != $personel->id
                ) {

                    $marketing_region = $spv_is_active_mm->id;
                    switch ($current_marketing_level) {
                        case 'mdm':
                            $marketing_region = $personel->id;
                            break;

                        default:
                            break;
                    }

                    $region->personel_id = $marketing_region;
                    $region->save();
                }

            });
    }

    /**
     * sub region in region that does not handled by supervisor
     * or him self, will take over by marketing region
     *
     * @param Collection $unmatch_region
     * @param [type] $personel
     * @return void
     */
    public static function subRegionTakeOver(Collection $unmatch_regions, $personel)
    {
        return SubRegion::query()
            ->where("region_id", $unmatch_regions->pluck("id")->toArray())
            ->where("personel_id", $personel->id)
            ->get()
            ->each(function ($sub_region) use ($unmatch_regions) {
                $sub_region->personel_id = $unmatch_regions
                    ->filter(function ($region) use ($sub_region) {
                        return $region->id == $sub_region->region_id;
                    })
                    ->first()
                ?->personel_id;
                $sub_region->save();
                $sub_region->refresh();
            });
    }

    /**
     * district in region does not handled by supervisor
     * or him self, and district handled by marketing
     * will take over by marketing region, marketing
     * subordinate old marketing will revoke from
     * district
     * @param Collection $unmatch_sub_regions
     * @return void
     */
    public static function districtTakeOver(Collection $unmatch_sub_regions, Personel $personel)
    {
        /**
         * marketing district revoke from district
         * and applicator set to null
         */
        return MarketingAreaDistrict::query()
            ->with([
                "subRegion",
            ])
            ->whereIn("sub_region_id", $unmatch_sub_regions->pluck("id")->toArray())
            ->get()
            ->each(function ($district) use ($unmatch_sub_regions) {
                $district->personel_id = $unmatch_sub_regions
                    ->filter(function ($sub_region) use ($district) {
                        return $sub_region->id == $district->sub_region_id;
                    })
                    ->first()
                ?->personel_id;
                $district->applicator_id = null;
                $district->save();
                $district->refresh();
            });
    }

    public static function retailerTakeOver(Collection $unmatch_sub_regions, Personel $personel)
    {
        /* dealer in district area */
        $dealer_in_district = DB::table('dealers as d')
            ->select("d.id", "ms.personel_id", "ad.district_id")
            ->join("address_with_details as ad", "ad.parent_id", "d.id")
            ->join("marketing_area_districts as md", "ad.district_id", "md.district_id")
            ->join("marketing_area_sub_regions as ms", "ms.id", "md.sub_region_id")
            ->whereNull("ad.deleted_at")
            ->whereNull("d.deleted_at")
            ->whereNull("md.deleted_at")
            ->whereNull("ms.deleted_at")
            ->whereIn("ms.id", $unmatch_sub_regions->pluck("id")->unique()->toArray())
            ->where("ad.type", "dealer")
            ->get();

        /* sub dealer in district area */
        $sub_dealer_in_district = DB::table('sub_dealers as d')
            ->select("d.id", "ms.personel_id", "ad.district_id")
            ->join("address_with_details as ad", "ad.parent_id", "d.id")
            ->join("marketing_area_districts as md", "ad.district_id", "md.district_id")
            ->join("marketing_area_sub_regions as ms", "ms.id", "md.sub_region_id")
            ->whereNull("ad.deleted_at")
            ->whereNull("d.deleted_at")
            ->whereNull("md.deleted_at")
            ->whereNull("ms.deleted_at")
            ->whereIn("md.sub_region_id", $unmatch_sub_regions->pluck("id")->unique()->toArray())
            ->where("ad.type", "sub_dealer")
            ->select("d.id")
            ->get();

        /**
         * dealer retailer (R1, R2, R3) and distributor(D1, D2) take take over
         */
        Dealer::query()
            ->whereIn("id", $dealer_in_district->pluck("id")->toArray())
            ->get()
            ->each(function ($dealer) use ($dealer_in_district) {
                $marketing_sub_region = $dealer_in_district
                    ->filter(function ($district) use ($dealer) {
                        return $dealer->id == $district->id;
                    })
                    ->first()?->personel_id;
                $dealer->personel_id = $marketing_sub_region;
                $dealer->save();
            });

        // dd(
        //     $unmatch_sub_regions->pluck("personel_id", "id"),
        //     $dealer_in_district,
        //     Dealer::query()
        //         ->whereIn("id", $dealer_in_district->pluck("id")->toArray())
        //         ->get()
        //         ->pluck("personel_id", "id"),
        //     $dealer_in_district = DB::table('dealers as d')
        //         ->whereIn("id", $dealer_in_district->pluck("id")->unique()->toArray())
        //         ->count()
        // );

        /**
         * sub dealer retailer (R1, R2, R3)take take over
         */
        SubDealer::query()
            ->whereIn("id", $dealer_in_district->pluck("id")->toArray())
            ->get()
            ->each(function ($ub_dealer) use ($sub_dealer_in_district) {
                $ub_dealer->personel_id = $sub_dealer_in_district
                    ->filter(function ($district) use ($ub_dealer) {
                        return $ub_dealer->id == $district->id;
                    })
                    ->first()?->personel_id;
                $ub_dealer->save();
            });
    }

    /**
     * distributor D1 take over, if current is RMC than take over by active MM
     * if current is MDM than take over by MDM
     *
     * @param Collection $area_sub_region
     * @param [type] $take_over_by
     * @return void
     */
    public static function distributorD1TakeOver(Collection $area_sub_region, $take_over_by)
    {
        /* dealer in district area */
        $distributor_d1_in_region = DB::table('dealers as d')
            ->select("d.id", "ad.district_id")
            ->join("address_with_details as ad", "ad.parent_id", "d.id")
            ->join("marketing_area_districts as md", "ad.district_id", "md.district_id")
            ->join("marketing_area_sub_regions as ms", "ms.id", "md.sub_region_id")
            ->join("agency_levels as ag", "ag.id", "d.agency_level_id")
            ->whereNull("ad.deleted_at")
            ->whereNull("d.deleted_at")
            ->whereNull("md.deleted_at")
            ->whereNull("ms.deleted_at")
            ->whereIn("ms.id", $area_sub_region->pluck("id")->unique()->toArray())
            ->whereIn("ag.name", agency_level_D1())
            ->where("ad.type", "dealer")
            ->get();

            // dd([
            //     "dealer" => $distributor_d1_in_region,
            //     "sub region " => $area_sub_region->pluck("id")->unique()->toArray(),
            //     "take by" => $take_over_by,
            // ]);


        /**
         * distributor(D1) take take over
         */
        Dealer::query()
            ->whereIn("id", $distributor_d1_in_region->pluck("id")->toArray())
            ->get()
            ->each(function ($dealer) use ($take_over_by) {
                // dump([
                //     "id" => $dealer->id,
                //     "before" => $dealer->personel_id
                // ]);

                $dealer->personel_id = $take_over_by;
                $dealer->save();

                // dump([
                //     "after" => $dealer->personel_id
                // ]);
            });

    }

    /**
     * update marketing region except mm and current marketing
     *
     * @param Collection $regions
     * @param Personel $personel
     * @param [type] $spv_is_active_mm
     * @return void
     */
    public static function marketingRegionSupervisorUpdate(?Collection $regions, Personel $personel, $spv_is_active_mm)
    {
        Personel::query()
            ->whereIn("id", $regions->pluck("personel_id")->toArray())
            ->where("supervisor_id", "!=", $personel->supervisor_id)
            ->where("id", "!=", $personel->id)
            ->get()
            ->each(function ($personel) use ($spv_is_active_mm) {
                $personel->supervisor_id = $spv_is_active_mm->id;
                $personel->save();
            });
    }

    /**
     * all marketing sub region in region current marketing, supervisor
     * update to active MM
     *
     * @param Collection $area_sub_region
     * @param Personel $personel
     * @param [type] $spv_is_active_mm
     * @return void
     */
    public static function marketingSubRegionSupervisorUpdate(Collection $area_sub_region, Personel $personel, $spv_is_active_mm)
    {
        $all_marketing_in_region_sub_region = DB::table('marketing_area_sub_regions')
            ->whereIn("region_id", $area_sub_region->pluck("region_id")->unique()->toArray())
            ->whereNull("deleted_at")
            ->get()
            ->pluck("personel_id")
            ->toArray();

        Personel::query()
            ->whereIn("id", $all_marketing_in_region_sub_region)
            ->where("supervisor_id", "!=", $personel->supervisor_id)
            ->where("id", "!=", $personel->id)
            ->get()
            ->each(function ($personel) use ($spv_is_active_mm) {
                $personel->supervisor_id = $spv_is_active_mm->id;
                $personel->save();
            });
    }
}
