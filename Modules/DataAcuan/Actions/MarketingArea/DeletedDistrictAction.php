<?php

namespace Modules\DataAcuan\Actions\MarketingArea;

use Illuminate\Support\Facades\DB;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;

class DeletedDistrictAction
{
    /**
     * deleted district mean all dealer and sub dealer
     * will revoke from marketing and handled by
     * jabamas office
     *
     * @param MarketingAreaDistrict $district
     * @return void
     */
    public function __invoke(MarketingAreaDistrict $district)
    {
        $dealer_district = DB::table('address_with_details')
            ->whereNull("deleted_at")
            ->where("district_id", $district->district_id)
            ->where("type", "dealer")
            ->get()
            ->pluck("parent_id")
            ->toArray();

        Dealer::query()
            ->whereIn("id", $dealer_district)
            ->get()
            ->each(function ($dealer) {
                $dealer->personel_id = null;
                $dealer->save();
            });

        $dealer_district = DB::table('address_with_details')
            ->whereNull("deleted_at")
            ->where("district_id", $district->district_id)
            ->where("type", "sub_dealer")
            ->get()
            ->pluck("parent_id")
            ->toArray();

        SubDealer::query()
            ->whereIn("id", $dealer_district)
            ->get()
            ->each(function ($sub_dealer) {
                $sub_dealer->personel_id = null;
                $sub_dealer->save();
            });

    }
}
