<?php

namespace Modules\Invoice\Listeners;

use Modules\PointMarketing\Entities\PointMarketing;
use Modules\SalesOrder\Entities\LogWorkerPointMarketing;
use Modules\SalesOrder\Entities\SalesOrderDetail;

class MarketingPointAccumulationListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(SalesOrderDetail $sales_order_detail, PointMarketing $point_marketing, LogWorkerPointMarketing $log_worker_point_marketing)
    {
        $this->point_marketing = $point_marketing;
        $this->sales_order_detail = $sales_order_detail;
        $this->log_worker_point_marketing = $log_worker_point_marketing;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $order_year = confirmation_time($event->sales_order) ? confirmation_time($event->sales_order)->format("Y") : now()->format("Y");
        $total_point_order = $this->sales_order_detail->where("sales_order_id", $event->sales_order->id)->sum("marketing_point");

        $log_worker_point_marketing = $this->log_worker_point_marketing->firstOrCreate([
            "sales_order_id" => $event->sales_order->id,
            "is_count" => "1",
        ]);

        $personel_id = $event->sales_order->personel_id;
        $point_marketing = null;
        if ($personel_id) {

            $point_marketing = $this->point_marketing->query()
                ->where('personel_id', $personel_id)
                ->where('year', $order_year)
                ->first();

            if ($point_marketing) {

                if ($log_worker_point_marketing->wasRecentlyCreated == true) {
                    $point_marketing->marketing_point_total += $total_point_order;
                    $point_marketing->save();

                    return $point_marketing;
                }

            } else {
                $point_marketing = $this->point_marketing->create([
                    "personel_id" => $personel_id,
                    "year" => $order_year,
                    "marketing_point_total" => $total_point_order,
                    "marketing_point_active" => 0,
                    "marketing_point_adjustment" => 0,
                    "marketing_point_redeemable" => 0,
                    "status" => "not_redeemed",
                ]);
            }

            return $total_point_order;
            return $point_marketing;

        } else {
            return "no marketing found in order";
        }
    }
}
