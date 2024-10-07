<?php

namespace Modules\Personel\Listeners;

use Spatie\Activitylog\Contracts\Activity;
use Modules\Personel\Entities\MarketingFee;
use Modules\Personel\Traits\FeeMarketingTrait;
use Modules\Personel\Events\PersoneJoinDateEvent;
use Modules\SalesOrder\Entities\FeeSharingSoOrigin;
use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;

class RecalculateFeeMarketingListener
{
    use FeeMarketingTrait;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        FeeTargetSharingSoOrigin $fee_target_sharing_origin,
        FeeSharingSoOrigin $fee_sharing_origin,
        MarketingFee $marketing_fee,
    ) {
        $this->fee_target_sharing_origin = $fee_target_sharing_origin;
        $this->fee_sharing_origin = $fee_sharing_origin;
        $this->marketing_fee = $marketing_fee;
        $this->year = now()->format("Y");
        $this->quarter = now()->quarter;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(PersoneJoinDateEvent $event)
    {
        /*
        |----------------------------------
        | RECALCULATE FEE MARKETING
        |----------------------------
        |*/

        /* fee reguler */
        /* fee marketing trait */
        $marketing_fee_total = $this->feeMarketingRegulerTotal($event->personel->id, $this->year, $this->quarter);
        $marketing_fee_active = $this->feeMarketingRegulerActive($event->personel->id, $this->year, $this->quarter);

        /* fe target */
        /* fee marketing trait */
        $marketing_fee_target_total = $this->feeMarketingTargetTotal($event->personel->id, $this->year, $this->quarter);
        $marketing_fee_target_active = $this->feeMarketingTargetActive($event->personel->id, $this->year, $this->quarter);

        $marketing_fee = $this->marketing_fee->firstOrCreate([
            "personel_id" => $event->personel->id,
            "year" => $this->year,
            "quarter" => $this->quarter,
        ], [
            "fee_reguler_total" => 0,
            "fee_reguler_settle" => 0,
            "fee_target_total" => 0,
            "fee_target_settle" => 0,
        ]
        );

        $old_fee = [
            "personel_id" => $event->personel->id,
            "year" => $this->year,
            "quarter" => $this->quarter,
            "fee_reguler_total" => $marketing_fee->fee_reguler_total,
            "fee_reguler_settle" => $marketing_fee->fee_reguler_settle,
            "fee_target_total" => $marketing_fee->fee_target_total,
            "fee_target_settle" => $marketing_fee->fee_target_settle,
        ];

        $marketing_fee->fee_reguler_total = $marketing_fee_total;
        $marketing_fee->fee_reguler_settle = $marketing_fee_active;
        $marketing_fee->fee_target_total = $marketing_fee_target_total;
        $marketing_fee->fee_target_settle = $marketing_fee_target_active;
        $marketing_fee->save();

        $test = activity()
            ->causedBy(auth()->id())
            ->performedOn($marketing_fee)
            ->withProperties([
                "old" => $old_fee,
                "attributes" => $marketing_fee,
            ])
            ->tap(function (Activity $activity) {
                $activity->log_name = 'sync';
            })
            ->log('marketing point syncronize');

        return [
            "marketing_fee_total" => $marketing_fee_total,
            "marketing_fee_active" => $marketing_fee_active,
            "marketing_fee_target_total" => $marketing_fee_target_total,
            "marketing_fee_target_active" => $marketing_fee_target_active,
            "log" => $test
        ];
    }
}
