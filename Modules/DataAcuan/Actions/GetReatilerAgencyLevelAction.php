<?php

namespace Modules\DataAcuan\Actions;

use Modules\DataAcuan\Entities\AgencyLevel;

class GetReatilerAgencyLevelAction
{
    public function __invoke()
    {
        return AgencyLevel::query()
            ->whereIn("name", ["R1", "R2", "R3"])
            ->get();

    }
}
