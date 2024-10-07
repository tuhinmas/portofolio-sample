<?php

namespace Modules\KiosDealer\Actions\Address;

use Modules\Address\Entities\Address;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;

class SyncAddressWithAreaAction
{
    public function __invoke(Address $address)
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
