<?php

namespace Modules\Personel\Actions\Point;

use Modules\Personel\Entities\Personel;

class RecalculateAllMarketingPointAction
{
    public function __invoke($sales_order = null)
    {
        return Personel::query()
            ->whereHas("salesOrder", function ($QQQ) use ($current_year) {
                return $QQQ->pointMarketingByYear($current_year);

            })
            ->orWhereHas("position", function ($QQQ) {
                return $QQQ->whereIn("name", marketing_positions());
            })
            ->get()
            ->each(function ($personel) {

            });
    }
}
