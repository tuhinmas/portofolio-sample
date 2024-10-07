<?php

namespace Modules\Invoice\Listeners;

use App\Traits\DistributorStock;
use Carbon\Carbon;
use Modules\Invoice\Events\MarketingPointActiveEvent;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Traits\PointMarketingTrait;
use Modules\PointMarketing\Entities\MarketingPointAdjustment;
use Modules\PointMarketing\Entities\PointMarketing;
use Modules\SalesOrder\Entities\LogWorkerPointMarketing;
use Modules\SalesOrder\Entities\LogWorkerPointMarketingActive;
use Modules\SalesOrder\Entities\LogWorkerSalesPoint;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;

class MarketingPointActiveListener
{
    use PointMarketingTrait;
    use DistributorStock;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        LogWorkerPointMarketingActive $log_worker_point_marketing_active,
        MarketingPointAdjustment $marketing_point_adjustment,
        LogWorkerPointMarketing $log_worker_point_marketing,
        LogWorkerSalesPoint $log_worker_sales_point,
        SalesOrderDetail $sales_order_detail,
        PointMarketing $point_marketing,
        SalesOrder $sales_order,
        Personel $personel,
    ) {
        $this->log_worker_point_marketing_active = $log_worker_point_marketing_active;
        $this->log_worker_point_marketing = $log_worker_point_marketing;
        $this->marketing_point_adjustment = $marketing_point_adjustment;
        $this->log_worker_sales_point = $log_worker_sales_point;
        $this->sales_order_detail = $sales_order_detail;
        $this->point_marketing = $point_marketing;
        $this->sales_order = $sales_order;
        $this->personel = $personel;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(MarketingPointActiveEvent $event)
    {
        $personel_id = $event->sales_order->personel_id;

        if ($personel_id) {

            /**
             * order will be condidered active if settle in the same year with confirmation
             * date or in the different year but less then 60 days (according
             * data reference)
             */
            $is_order_considerd_active = $this->isOrderActivePoint($event->sales_order);

            if (in_array($event->sales_order->status, ["confirmed", "pending"])) {
                
                /* add point active to marketing if ot has never een added */
                $this->addPointActiveToMarketing($is_order_considerd_active, $event->sales_order);
            }

            return "point active checked";

            /*
            |--------------------------
            | PENDING
            | ---------------
             */
            if ($event->sales_order->type == 1) {

                $log_worker_point_marketing = $this->log_worker_point_marketing
                    ->where("sales_order_id", $event->sales_order->id)
                    ->where("is_active", false)
                    ->first();

                if ($log_worker_point_marketing) {
                    $total_active_point_order = $this->sales_order_detail
                        ->where("sales_order_id", $event->sales_order->id)
                        ->whereHas("sales_order", function ($QQQ) {
                            return $QQQ->whereHas("logWorkerPointMarketing", function ($QQQ) {
                                return $QQQ->where("is_active", false);
                            });
                        })
                        ->sum("marketing_point");
                } else {
                    $total_active_point_order = 0;
                }

                $order_year = $event->sales_order->invoice->created_at->format("Y");
                $point_marketing = null;

                $log_worker_point_marketing_active = $this->log_worker_point_marketing_active->firstOrCreate([
                    "sales_order_id" => $event->sales_order,
                ]);

                $log_worker_point_marketing = $this->log_worker_point_marketing
                    ->where("sales_order_id", $event->sales_order->id)
                    ->where("is_count", true)
                    ->update([
                        "is_active" => "1",
                    ]);

                $point_marketing = $this->point_marketing->query()
                    ->where('personel_id', $personel_id)
                    ->where('year', $order_year)
                    ->first();

                /* maximum payment days */
                $maximum_settle_days = maximum_settle_days(now()->format("Y"));
                $is_point_counted = false;
                if (Carbon::parse($event->payment->payment_date)->format("Y") == Carbon::parse($event->sales_order->invoice->created_at)->format("Y")) {
                    $is_point_counted = true;
                } elseif (Carbon::parse($event->payment->payment_date)->format("Y") != Carbon::parse($event->sales_order->invoice->created_at)->format("Y")) {
                    if ($event->sales_order->invoice->created_at->diffInDays($event->payment->payment_date, false) <= ($maximum_settle_days ? $maximum_settle_days : 60)) {
                        $is_point_counted = true;
                    }
                }

                if ($is_point_counted) {
                    if ($point_marketing) {
                        $point_marketing->marketing_point_active += $total_active_point_order;
                        $point_marketing->marketing_point_redeemable += $total_active_point_order;
                        $point_marketing->save();

                        return $point_marketing;
                    } else {
                        $point_marketing = $this->point_marketing->create([
                            "personel_id" => $personel_id,
                            "year" => $order_year,
                            "marketing_point_total" => $total_active_point_order,
                            "marketing_point_active" => $total_active_point_order,
                            "marketing_point_adjustment" => 0,
                            "marketing_point_redeemable" => $total_active_point_order,
                            "status" => "not_redeemed",
                        ]);
                    }
                } else {
                    $total_active_point_order = 0;
                }

                return $point_marketing;

            }

            /* indirect sale active point marketing date is according last receiving good date */
            else {

                /* if there has no receiving found */
                if (!$event->sales_order->lastReceivingGoodIndirect) {
                    $total_active_point_order = 0;
                    return "no receiving good indirect sale found, active point 0";
                } else {

                    $log_worker_point_marketing = $this->log_worker_point_marketing
                        ->where("sales_order_id", $event->sales_order->id)
                        ->where("is_active", false)
                        ->first();

                    if ($log_worker_point_marketing) {
                        $total_active_point_order = $this->sales_order_detail
                            ->where("sales_order_id", $event->sales_order->id)
                            ->whereHas("sales_order", function ($QQQ) {
                                return $QQQ->whereHas("logWorkerPointMarketing", function ($QQQ) {
                                    return $QQQ->where("is_active", false);
                                });
                            })
                            ->sum("marketing_point");
                    } else {
                        $total_active_point_order = 0;
                    }

                    $log_worker_point_marketing_active = $this->log_worker_point_marketing_active->firstOrCreate([
                        "sales_order_id" => $event->sales_order,
                    ]);

                    $log_worker_point_marketing = $this->log_worker_point_marketing
                        ->where("sales_order_id", $event->sales_order->id)
                        ->where("is_count", true)
                        ->update([
                            "is_active" => "1",
                        ]);

                    $order_year = Carbon::parse($event->sales_order->lastReceivingGoodIndirect->date_received)->format("Y");
                    $point_marketing = null;

                    $point_marketing = $this->point_marketing->query()
                        ->where('personel_id', $personel_id)
                        ->where('year', $order_year)
                        ->first();

                    if ($point_marketing) {

                        if ($log_worker_point_marketing > 0) {
                            $point_marketing->marketing_point_active += $total_active_point_order;
                            $point_marketing->marketing_point_redeemable += $total_active_point_order;
                            $point_marketing->save();

                            return $point_marketing;
                        }

                    } else {
                        $point_marketing = $this->point_marketing->create([
                            "personel_id" => $personel_id,
                            "year" => $order_year,
                            "marketing_point_total" => $total_active_point_order,
                            "marketing_point_active" => $total_active_point_order,
                            "marketing_point_adjustment" => 0,
                            "marketing_point_redeemable" => $total_active_point_order,
                            "status" => "not_redeemed",
                        ]);
                    }
                }
            }
        } else {
            return "no marketing found in order";
        }
    }
}
