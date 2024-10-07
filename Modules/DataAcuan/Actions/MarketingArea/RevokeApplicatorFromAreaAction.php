<?php

namespace Modules\DataAcuan\Actions\MarketingArea;

use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\Personel\Entities\Personel;

class RevokeApplicatorFromAreaAction
{
    public function __invoke(Personel $personel)
    {
        return MarketingAreaDistrict::query()
            ->where("applicator_id", $personel->id)
            ->get()
            ->each(function ($district) {
                $district->applicator_id = null;
                $district->save();
            });
    }
}
