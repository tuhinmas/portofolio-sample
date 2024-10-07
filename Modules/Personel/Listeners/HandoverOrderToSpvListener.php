<?php

namespace Modules\Personel\Listeners;

use App\Traits\MarketingArea;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\FeePosition;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\Personel\Entities\LogMarketingFeeCounter;
use Modules\Personel\Entities\MarketingFee;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Events\PersonelInactiveEvent;
use Modules\Personel\Traits\FeeMarketingTrait;
use Modules\Personel\Traits\PointMarketingTrait;
use Modules\PointMarketing\Entities\MarketingPointAdjustment;
use Modules\PointMarketing\Entities\PointMarketing;
use Modules\SalesOrder\Entities\FeeSharingSoOrigin;
use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;
use Modules\SalesOrder\Entities\LogWorkerPointMarketing;
use Modules\SalesOrder\Entities\LogWorkerPointMarketingActive;
use Modules\SalesOrder\Entities\LogWorkerSalesFee;
use Modules\SalesOrder\Entities\LogWorkerSalesPoint;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;

class HandoverOrderToSpvListener
{
    use PointMarketingTrait;
    use FeeMarketingTrait;
    use MarketingArea;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        LogWorkerPointMarketingActive $log_worker_point_marketing_active,
        MarketingPointAdjustment $marketing_point_adjustment,
        FeeTargetSharingSoOrigin $fee_target_sharing_origin,
        LogWorkerPointMarketing $log_worker_point_marketing,
        LogMarketingFeeCounter $log_marketing_fee_counter,
        LogWorkerSalesPoint $log_worker_sales_point,
        LogWorkerSalesFee $log_worker_sales_fee,
        FeeSharingSoOrigin $fee_sharing_origin,
        SalesOrderDetail $sales_order_detail,
        PointMarketing $point_marketing,
        MarketingFee $marketing_fee,
        FeePosition $fee_position,
        SalesOrder $sales_order,
        SubDealer $sub_dealer,
        Personel $personel,
        Dealer $dealer
    ) {
        $this->log_worker_point_marketing_active = $log_worker_point_marketing_active;
        $this->log_worker_point_marketing = $log_worker_point_marketing;
        $this->marketing_point_adjustment = $marketing_point_adjustment;
        $this->fee_target_sharing_origin = $fee_target_sharing_origin;
        $this->log_marketing_fee_counter = $log_marketing_fee_counter;
        $this->log_worker_sales_point = $log_worker_sales_point;
        $this->log_worker_sales_fee = $log_worker_sales_fee;
        $this->sales_order_detail = $sales_order_detail;
        $this->fee_sharing_origin = $fee_sharing_origin;
        $this->point_marketing = $point_marketing;
        $this->marketing_fee = $marketing_fee;
        $this->fee_position = $fee_position;
        $this->sales_order = $sales_order;
        $this->sub_dealer = $sub_dealer;
        $this->personel = $personel;
        $this->dealer = $dealer;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(PersonelInactiveEvent $event)
    {
        /**
         * sales order handover to supervisor
         */
        $status_fee_L1 = DB::table('status_fee')->whereNull("deleted_at")->where("name", "L1")->first();
        $sales_orders = $this->sales_order->query()
            ->with([
                "feeSharingOrigin",
                "feeTargetSharingOrigin",
            ])
            ->where("personel_id", $event->personel->id)
            ->when($event->previous_status == 2, function ($QQQ) {
                return $QQQ
                    ->where("is_marketing_freeze", true)
                    ->whereIn("status", ["confirmed", "submited", "draft", "pending", "onhold", "reviewed"]);
            })

            ->when(!$event->previous_status, function ($QQQ) {
                return $QQQ->whereIn("status", ["submited", "draft", "onhold", "reviewed"]);
            })
            ->when($event->previous_status == 1, function ($QQQ) {
                return $QQQ->whereIn("status", ["submited", "draft", "onhold", "reviewed"]);
            })
            ->get()
            ->each(function ($order) use ($status_fee_L1, $event) {

                /* update fee target sharing origin */
                $this->fee_target_sharing_origin
                    ->where("sales_order_id", $order->id)
                    ->where("personel_id", $order->personel_id)
                    ->get()
                    ->each(function ($origin) use ($event) {
                        $origin->personel_id = $event->personel->supervisor?->id;
                        $origin->save();
                    });

                /* update fee sharing origin */
                $this->fee_sharing_origin
                    ->where("sales_order_id", $order->id)
                    ->where("personel_id", $order->personel_id)
                    ->get()
                    ->each(function ($origin) use ($event) {
                        $origin->personel_id = $event->personel->supervisor?->id;
                        $origin->save();
                    });

                $order->personel_id = $event->personel->supervisor?->id;
                $order->status_fee_id = $status_fee_L1?->id;
                $order->save();
            });

        /*
        |--------------------------------------------------
        | recalculate marketing fee current quarter
        | for replaced marketing
        |-------------------------------------------
        |
         */
        $this->feeMarketingRegulerTotal($event->personel->id, now()->format("Y"), now()->quarter);
        $this->feeMarketingRegulerActive($event->personel->id, now()->format("Y"), now()->quarter);

        $this->feeMarketingTargetTotal($event->personel->id, now()->format("Y"), now()->quarter);
        $this->feeMarketingTargetActive($event->personel->id, now()->format("Y"), now()->quarter);

        if ($event->personel->supervisor) {
            $this->feeMarketingRegulerTotal($event->personel->supervisor->id, now()->format("Y"), now()->quarter);
            $this->feeMarketingRegulerActive($event->personel->supervisor->id, now()->format("Y"), now()->quarter);

            $this->feeMarketingTargetTotal($event->personel->supervisor->id, now()->format("Y"), now()->quarter);
            $this->feeMarketingTargetActive($event->personel->supervisor->id, now()->format("Y"), now()->quarter);
        }

        /**
         * recalculate marketing fee current quarter
         * for replacement marketing
         */
        if ($event->personel->supervisor) {
            $this->feeMarketingRegulerTotal($event->personel->supervisor->id, now()->format("Y"), now()->quarter);
            $this->feeMarketingRegulerActive($event->personel->supervisor->id, now()->format("Y"), now()->quarter);

            $this->feeMarketingTargetTotal($event->personel->supervisor->id, now()->format("Y"), now()->quarter);
            $this->feeMarketingTargetActive($event->personel->id, now()->format("Y"), now()->quarter);
        }

        /*
        |-----------------------------------
        | Point Marketing recalculation
        |-------------------------------
        |
         */
        if ($event->personel->supervisor) {
            $this->recalcultePointMarketingTotal($event->personel->supervisor?->id, now()->format("Y"));
            $this->recalcultePointMarketingActive($event->personel->supervisor?->id, now()->format("Y"));
        }

        $this->recalcultePointMarketingTotal($event->personel->id, now()->format("Y"));
        $this->recalcultePointMarketingActive($event->personel->id, now()->format("Y"));

        /**
         * marketing area that handled by marketing that change to inactive
         * will hanover to his supervisor
         */
        if ($event->personel->supervisor) {

            /* marketingArea trait */
            $this->updateMarketingAreaToSupervisor($event->personel->id, $event->personel->supervisor?->id);
        }

        /* personel has no supervisor */
        else {
            $personelMM = $this->personel->query()
                ->whereHas("position", function ($query) {
                    return $query->where("is_mm", "1");
                })
                ->where("status", "1")
                ->where("id", "!=", $event->personel->id)
                ->latest()
                ->first();
            if ($personelMM) {
                $this->updateMarketingAreaToSupervisor($event->personel->id, $personelMM->id);
            }
        }

        return $sales_orders;
    }
}
