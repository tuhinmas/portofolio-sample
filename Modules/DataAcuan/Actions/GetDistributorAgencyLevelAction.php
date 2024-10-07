<?php

namespace Modules\DataAcuan\Actions;

use Modules\DataAcuan\Entities\AgencyLevel;

class GetDistributorAgencyLevelAction
{
    public function __invoke()
    {
        return AgencyLevel::query()
            ->whereIn("name", ["D1", "D2"])
            ->get();

    }
}
