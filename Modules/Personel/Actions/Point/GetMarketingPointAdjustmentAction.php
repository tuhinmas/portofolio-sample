<?php

namespace Modules\Personel\Actions\Point;

use Modules\PointMarketing\Entities\MarketingPointAdjustment;

class GetMarketingPointAdjustmentAction
{
    public function __invoke($personel_id, $year): int
    {
        return MarketingPointAdjustment::query()
            ->whereHas("pointMarketing", function ($QQQ) use ($personel_id, $year) {
                return $QQQ
                    ->where("personel_id", $personel_id)
                    ->where("year", $year);
            })
            ->get()
            ->sum("adjustment_poin");
    }
}
