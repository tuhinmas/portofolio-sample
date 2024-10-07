<?php

namespace App\Traits;

use App\Traits\SuperVisorCheckV2;
use Modules\Distributor\Entities\DistributorArea;
use Modules\Distributor\Entities\DistributorContract;

/**
 *
 */
trait FilterByArea
{
    use SuperVisorCheckV2;

    public function scopeByArea($district_id)
    {
        $contract_id = DistributorArea::query()
            ->where("district_id", $district_id)
            ->whereHas("contract")
            ->with("contract")
            ->get()
            ->pluck("contract.id");

        $dealer_id = DistributorContract::query()
            ->whereIn("id", $contract_id)
            ->where("contract_start", "<=", now()->format("Y-m-d"))
            ->where("contract_end", ">=", now()->format("Y-m-d"))
            ->get()
            ->pluck("dealer_id");

        return $dealer_id;
    }
}
