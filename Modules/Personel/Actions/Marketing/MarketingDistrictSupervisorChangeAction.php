<?php

namespace Modules\Personel\Actions\Marketing;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\Personel\Entities\Personel;

class MarketingDistrictSupervisorChangeAction
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
         * district handle by marketing
         */
        $area_district = DB::table('marketing_area_districts')
            ->whereNull("deleted_at")
            ->where("personel_id", $personel->id)
            ->get();

        if ($area_district->count() <= 0) {
            return 0;
        }

        /**
         * sub region that not handled by his supervisor
         * or him self, but district in it
         */
        $unmatch_sub_region = DB::table('marketing_area_sub_regions')
            ->whereNull("deleted_at")
            ->whereIn("id", $area_district->pluck("sub_region_id")->unique()->toArray())
            ->where("personel_id", "!=", $personel->supervisor_id)
            ->where("personel_id", "!=", $personel->id)
            ->select("*")
            ->get();

        if ($unmatch_sub_region->count() > 0) {
            self::districtTakeOver($unmatch_sub_region, $personel);
            self::retailerTakeOver($unmatch_sub_region, $personel);
        }
    }

    /**
     * sub region that not handled by his supervisor
     * or him self, but district in it
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
            ->where("personel_id", $personel->id)
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
            ->whereIn("md.sub_region_id", $unmatch_sub_regions->pluck("id")->unique()->toArray())
            ->where("d.personel_id", $personel->id)
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
            ->where("d.personel_id", $personel->id)
            ->where("ad.type", "sub_dealer")
            ->select("d.id")
            ->get();

        /**
         * dealer retailer (R1, R2, R3) and distributor(D1, D2) take take over
         */
        Dealer::query()
            ->whereIn("id", $dealer_in_district->pluck("id")->unique()->toArray())
            ->where("personel_id", $personel->id)
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

        /**
         * sub dealer retailer (R1, R2, R3)take take over
         */
        SubDealer::query()
            ->whereIn("id", $sub_dealer_in_district->pluck("id")->unique()->toArray())
            ->where("personel_id", $personel->id)
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

}
