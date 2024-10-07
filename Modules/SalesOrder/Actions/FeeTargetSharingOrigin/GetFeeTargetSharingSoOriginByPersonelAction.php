<?php

namespace Modules\SalesOrder\Actions\FeeTargetSharingOrigin;

use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;

class GetFeeTargetSharingSoOriginByPersonelAction
{
    /**
     *
     * @param [type] $personel_id
     * @param [type] $year
     * @param [type] $quarter
     * @return void
     */
    public function __invoke($payload)
    {
        extract($payload);
        return FeeTargetSharingSoOrigin::query()
            ->with([
                "position",
                "salesOrderDetail",
                "salesOrder" => function ($QQQ) {
                    return $QQQ->with([
                        "invoice",
                    ]);
                },
                "salesOrderOrigin" => function ($QQQ) {
                    return $QQQ->with([
                        "direct" => function ($QQQ) {
                            return $QQQ->with([
                                "invoice",
                            ]);
                        },
                    ]);
                },
            ])
            ->whereHas("salesOrder", function ($QQQ) {
                return $QQQ
                    ->consideredOrder()
                    ->isOffice(false);
            })
            ->where("personel_id", $personel_id)
            ->whereYear("confirmed_at", $year)
            ->whereRaw("quarter(confirmed_at) = ?", $quarter)
            ->get();
    }
}
