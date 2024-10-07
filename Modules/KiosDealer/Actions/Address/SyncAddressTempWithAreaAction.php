<?php

namespace Modules\KiosDealer\Actions\Address;

use Modules\Address\Entities\AddressTemp;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;

class SyncAddressTempWithAreaAction
{
    public function __invoke(AddressTemp $address)
    {
        $area = MarketingAreaDistrict::query()
            ->with([
                "subRegion.region",
            ])
            ->whereHas("subRegion")
            ->where("district_id", $address->district_id)
            ->first();

        if ($area) {
            $address->area_id = $area->id;
            $address->sub_region_id = $area->sub_region_id;
            $address->region_id = $area->subRegion->region_id;
            $address->save();
        }

    }
}
