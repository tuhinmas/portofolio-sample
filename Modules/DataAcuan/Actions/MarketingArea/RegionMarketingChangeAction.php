<?php

namespace Modules\DataAcuan\Actions\MarketingArea;

use App\Traits\ChildrenList;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\DataAcuan\Entities\Region;
use Modules\DataAcuan\Entities\SubRegion;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\Personel\Entities\Personel;

class RegionMarketingChangeAction
{
    use ChildrenList;

    public function __invoke(Region $region, string $old_marketing_id)
    {
        /**
         * marketing sub region (RMC example) changes will
         * 1. district handled by old_marketing will tak over
         * 2. sub region handled by old marketing will take over
         * 3. retailer handled by old marketing will take over
         * 3. distributor D2 in region handled by old marketing will take over
         * 4. distributor D1 in region handled by old marketing will take over
         *
         */

        if ($region->personel_id == $old_marketing_id) {
            return 0;
        }

        /* sub_region take over */
        $sub_regions = self::subRegionTakeOver($region, $old_marketing_id);

        /* district take over */
        $districts = self::districtTakeOver($sub_regions, $region, $old_marketing_id);

        /* retailer / distributor take over */
        self::retailerAndDistributorTakeOver($region, $districts->pluck("district_id")->toArray(), $old_marketing_id);

        /* supervisor update and district take over in special condition  */
        self::personelUpdateSupervisor($region, $districts);
    }

    /**
     * district will take over by new marketing
     *
     * @param Region $region
     * @param [type] $old_marketing_id
     * @return void
     */
    public static function districtTakeOver(Collection $sub_region, Region $region, $old_marketing_id)
    {
        return MarketingAreaDistrict::query()
            ->with([
                "personel",
            ])
            ->whereIn("sub_region_id", $sub_region->pluck("id")->toArray())
            ->get()
            ->each(function ($district) use ($region, $old_marketing_id) {

                /** area district take over */
                if ($district->personel_id == $old_marketing_id) {
                    $district->personel_id = $region->personel_id;
                    $district->applicator_id = null;
                    $district->save();
                    $district->refresh();
                }
            });
    }

    /**
     * all sub region in region handled by old marketing will take over by new MDM
     *
     * @param Region $region
     * @param [type] $old_marketing_id
     * @return void
     */
    public static function subRegionTakeOver(Region $region, $old_marketing_id)
    {
        return SubRegion::query()
            ->where("region_id", $region->id)
            ->get()
            ->each(function ($sub_region) use ($old_marketing_id, $region) {
                if ($sub_region->personel_id == $old_marketing_id) {
                    $sub_region->personel_id = $region->personel_id;
                    $sub_region->save();
                    $sub_region->refresh();
                }
            });
    }

    /**
     * retailer in sub region area handled by old marketing
     * will take over by new marketing
     *
     * @param Region $region
     * @param array $districts
     * @param string $old_marketing_id
     * @return void
     */
    public static function retailerAndDistributorTakeOver(Region $region, array $districts, string $old_marketing_id)
    {
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
         * all dealer retailer (R1, R2, R3) and distributor(D1, D2) in this region
         * handled by old marketing will take take over
         */
        Dealer::query()
            ->whereIn("id", $dealer_in_district)
            ->where("personel_id", $old_marketing_id)
            ->get()
            ->each(function ($dealer) use ($region) {
                $dealer->personel_id = $region->personel_id;
                $dealer->save();
            });

        /**
         * all sub dealer retailer (R1, R2, R3) handled by old marketing take take over
         */
        SubDealer::query()
            ->whereIn("id", $sub_dealer_in_district)
            ->where("personel_id", $old_marketing_id)
            ->get()
            ->each(function ($ub_dealer) use ($region) {
                $ub_dealer->personel_id = $region->personel_id;
                $ub_dealer->save();
            });
    }

    /**
     * supervisor update
     *
     * @param Region $sub_region
     * @return void
     */
    public static function personelUpdateSupervisor(Region $region, Collection $district_in_sub_regions)
    {
        /* get personel with MM position */
        $personel_mm = Personel::query()
            ->whereHas("position", function ($QQQ) {
                return $QQQ->marketingManager();
            })
            ->where("status", "1")
            ->first();

        /* compare personel in region and personel mm */
        if ($region->personel_id !== $personel_mm->id) {

            /**
             * if personel in region is not same with personel mm
             * update supervisor personel region with
             * personel mm
             */
            $personel = Personel::query()
                ->where("id", $region->personel_id)
                ->get()
                ->each(function ($marketing) use ($personel_mm) {
                    $marketing->supervisor_id = $personel_mm->id;
                    $marketing->save();
                    $marketing->refresh();
                });
        }

        /* update supervisor marketing in sub region except MDM it self */
        $sub_region = SubRegion::query()
            ->with([
                "personel",
                "district" => function ($QQQ) {
                    return $QQQ->with([
                        "personel",
                    ]);
                },
            ])
            ->where("region_id", $region->id)
            ->whereNotNull("personel_id")
            ->get();

        $sub_region
            ->pluck("personel")
            ->unique("id")
            ->reject(function ($personel) use ($region) {
                return $personel->id == $region->personel_id;
            })
            ->each(function ($marketing) use ($region) {
                $marketing->supervisor_id = $region->personel_id;
                $marketing->save();
            });

        /**
         * update marketing district supervisor in sub region that handled
         * by new marketing except MDM it self
         */

        // dd(
        //     $sub_region
        //         ->pluck("district")
        //         ->collapse()
        //         ->reject(function ($district) use ($region) {
        //             return $district->personel_id == $region->personel_id;
        //         })
        //         ->pluck("personel")
        // );

        $sub_region
            ->pluck("district")
            ->collapse()
            ->reject(function ($district) use ($region) {
                return $district->personel_id == $region->personel_id;
            })
            ->pluck("personel")
            ->each(function ($marketing) use ($region) {
                $marketing->supervisor_id = $region->personel_id;
                $marketing->save();
            });

    }
}
