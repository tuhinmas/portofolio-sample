<?php

namespace Modules\Personel\Actions;

use Modules\SalesOrderV2\Entities\FeeTargetSharing;

class GetFeeTargetTotalActivePendingPerQuarterAction
{
    public function __invoke($payload)
    {

        /* destructing payload */
        extract($payload);
        return FeeTargetSharing::query()
            ->where("personel_id", $personel_id)
            ->where("year", $year)
            ->where("quarter", $quarter)
            ->get()
            ->sum("fee_shared_active_pending");
    }
}
