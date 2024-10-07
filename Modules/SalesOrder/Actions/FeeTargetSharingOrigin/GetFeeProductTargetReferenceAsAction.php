<?php

namespace Modules\SalesOrder\Actions\FeeTargetSharingOrigin;

use Modules\DataAcuan\Entities\Fee;

class GetFeeProductTargetReferenceAsAction
{
    public function __invoke($payload)
    {
        extract($payload);
        $fee_product_reguler = Fee::query()
            ->with([
                "parentRegulerByYearQuarter" => function ($QQQ) use ($year, $quarter) {
                    return $QQQ->byRegulerYearQuarterType($year, $quarter);
                },
            ])
            ->where("product_id", $product_id)
            ->where("quartal", $quarter)
            ->where("year", $year)
            ->where("type", "2")
            ->first();

        if ($fee_product_reguler) {
            if ($fee_product_reguler->parentRegulerByYearQuarter) {
                $fee_product_reguler = $fee_product_reguler->parentRegulerByYearQuarter;
            }
        }

        return $fee_product_reguler;
    }
}
