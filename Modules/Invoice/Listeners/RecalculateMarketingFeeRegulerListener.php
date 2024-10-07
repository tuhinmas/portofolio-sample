<?php

namespace Modules\Invoice\Listeners;

use Modules\Invoice\Events\FeeMarketingEvent;
use Modules\Personel\Entities\LogMarketingFeeCounter;
use Modules\Personel\Entities\MarketingFee;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Traits\FeeMarketingTrait;
use Modules\SalesOrder\Entities\FeeSharingSoOrigin;
use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Spatie\Activitylog\Contracts\Activity;

class RecalculateMarketingFeeRegulerListener
{
    use FeeMarketingTrait;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        FeeTargetSharingSoOrigin $fee_target_sharing_origin,
        LogMarketingFeeCounter $log_marketing_fee_counter,
        FeeSharingSoOrigin $fee_sharing_origin,
        SalesOrderDetail $sales_order_detail,
        MarketingFee $marketing_fee,
        SalesOrder $sales_order,
        Personel $personel,
    ) {
        $this->log_marketing_fee_counter = $log_marketing_fee_counter;
        $this->fee_target_sharing_origin = $fee_target_sharing_origin;
        $this->sales_order_detail = $sales_order_detail;
        $this->fee_sharing_origin = $fee_sharing_origin;
        $this->marketing_fee = $marketing_fee;
        $this->sales_order = $sales_order;
        $this->personel = $personel;

    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(FeeMarketingEvent $event)
    {
        if ($event->invoice->salesOrder->personel_id) {
            $personel = $this->personel->findOrFail($event->invoice->salesOrder->personel_id);

            /* marketing_fee_list */
            $marketing_fee_list = collect();

            $fee_sharing_origins = $this->fee_sharing_origin->query()
                ->where("sales_order_id", $event->invoice->sales_order_id)
                ->get()
                ->map(function ($origin) {
                    return $origin->personel_id;
                })
                ->reject(fn($Personel_id) => !$Personel_id)
                ->unique()
                ->each(function ($personel_id) use ($event, &$marketing_fee_list) {

                    /* fee reguler */
                    $marketing_fee_total = $this->feeMarketingRegulerTotal($personel_id, $event->invoice->created_at->format("Y"), $event->invoice->created_at->quarter, $event->invoice->salesOrder);
                    $marketing_fee_active = $this->feeMarketingRegulerActive($personel_id, $event->invoice->created_at->format("Y"), $event->invoice->created_at->quarter, $event->invoice->salesOrder);

                    for ($i = 1; $i < 5; $i++) {
                        $this->marketing_fee->firstOrCreate([
                            "personel_id" => $personel_id,
                            "year" => $event->invoice->created_at->format("Y"),
                            "quarter" => $i,
                        ], [
                            "fee_reguler_total" => 0,
                            "fee_reguler_settle" => 0,
                            "fee_target_total" => 0,
                            "fee_target_settle" => 0,
                        ]);
                    }

                    $marketing_fee = $this->marketing_fee->query()
                        ->where("personel_id", $personel_id)
                        ->where("year", $event->invoice->created_at->format("Y"))
                        ->where("quarter", $event->invoice->created_at->quarter)
                        ->first();

                    $old_fee = [
                        "personel_id" => $personel_id,
                        "year" => $event->invoice->created_at->format("Y"),
                        "quarter" => $event->invoice->created_at->quarter,
                        "fee_reguler_total" => $marketing_fee->fee_reguler_total,
                        "fee_reguler_settle" => $marketing_fee->fee_reguler_settle,
                    ];

                    $marketing_fee->fee_reguler_total += $marketing_fee_total;
                    $marketing_fee->fee_reguler_settle += $marketing_fee_active;
                    $marketing_fee->save();

                    $marketing_fee_list->push([
                        "personel_id" => $personel_id,
                        "year" => $event->invoice->created_at->format("Y"),
                        "quarter" => $event->invoice->created_at->quarter,
                        "fee_reguler_total" => $marketing_fee->fee_reguler_total,
                        "fee_reguler_settle" => $marketing_fee->fee_reguler_settle,
                    ]);

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

                });

            return $marketing_fee_list;
        }
    }
}
