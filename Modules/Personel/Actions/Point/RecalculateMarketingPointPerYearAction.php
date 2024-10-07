<?php

namespace Modules\Personel\Actions\Point;

use Spatie\Activitylog\Contracts\Activity;
use Modules\PointMarketing\Entities\PointMarketing;
use Modules\Personel\Actions\Point\GetMarketingPointTotalAction;
use Modules\Personel\Actions\Point\GetMarketingPointActiveAction;
use Modules\Personel\Actions\Point\GetMarketingPointAdjustmentAction;

class RecalculateMarketingPointPerYearAction
{
    public function __invoke($personel_id, $year, $sales_orders)
    {
        $point_total_action = new GetMarketingPointTotalAction();
        $point_active_action = new GetMarketingPointActiveAction();
        $point_adjustment = new GetMarketingPointAdjustmentAction();

        $marketing_point_total = $point_total_action($sales_orders);
        $marketing_point_active = $point_active_action($sales_orders, $year);
        $marketing_point_adjustment = $point_adjustment($personel_id, $year);

        $point_marketing = PointMarketing::firstOrCreate([
            "personel_id" => $personel_id,
            "year" => $year,
        ], [
            "marketing_point_total" => 0,
            "marketing_point_active" => 0,
            "marketing_point_adjustment" => 0,
            "marketing_point_redeemable" => 0,
        ]);

        $old_point = [
            "personel_id" => $personel_id,
            "marketing_point_total" => $point_marketing?->marketing_point_total,
            "marketing_point_active" => $point_marketing?->marketing_point_active,
            "marketing_point_adjustment" => $point_marketing?->marketing_point_adjustment,
            "marketing_point_redeemable" => $point_marketing?->marketing_point_redeemable,
            "status" => $point_marketing?->status,
            "year" => $point_marketing?->year,
        ];

        $point_marketing = PointMarketing::updateOrCreate([
            "personel_id" => $personel_id,
            "year" => $year,
        ], [
            "marketing_point_total" => $marketing_point_total,
            "marketing_point_active" => $marketing_point_active,
            "marketing_point_adjustment" => $marketing_point_adjustment,
            "marketing_point_redeemable" => $marketing_point_active + ($marketing_point_adjustment),
        ]);

        $test = activity()
            ->causedBy(auth()->id())
            ->performedOn($point_marketing)
            ->withProperties([
                "old" => $old_point,
                "attributes" => $point_marketing,
            ])
            ->tap(function (Activity $activity) {
                $activity->log_name = 'sync';
            })
            ->log('marketing point syncronize');

        return $point_marketing;
    }
}
