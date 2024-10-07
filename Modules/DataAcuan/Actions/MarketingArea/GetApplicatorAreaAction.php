<?php

namespace Modules\DataAcuan\Actions\MarketingArea;

use Modules\DataAcuan\Entities\MarketingAreaDistrict;

class GetApplicatorAreaAction
{
    public function __invoke($applicator_id)
    {
        return MarketingAreaDistrict::query()
            ->where("applicator_id", $applicator_id)
            ->get();
    }
}
