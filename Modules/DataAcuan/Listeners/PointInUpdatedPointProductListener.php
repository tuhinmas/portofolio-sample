<?php

namespace Modules\DataAcuan\Listeners;

use App\Traits\DistributorStock;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Events\PointInUpdatedPointProductEvent;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Traits\PointMarketingTrait;
use Modules\PointMarketing\Entities\MarketingPointAdjustment;
use Modules\PointMarketing\Entities\PointMarketing;
use Modules\SalesOrder\Entities\LogWorkerPointMarketing;
use Modules\SalesOrder\Entities\LogWorkerPointMarketingActive;
use Modules\SalesOrder\Entities\LogWorkerSalesPoint;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;

class PointInUpdatedPointProductListener
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
    public function handle(PointInUpdatedPointProductEvent $event)
    {
        /**
         * get all sales order detail on this year
         * according point product recently created
         */
        $sales_order_detail = $this->sales_order_detail->query()
            ->with([
                "sales_order" => function ($QQQ) {
                    return $QQQ
                        ->with([
                            "LogWorkerSalesPoint",
                            "logWorkerPointMarketing",
                            "logWorkerPointMarketingActive",
                        ])
                        ->confirmedOrderByYear(now()->format("Y"));
                },
            ])
            ->whereHas("sales_order", function ($QQQ) {
                return $QQQ->confirmedOrderByYear(now()->format("Y"));
            })
            ->where("product_id", $event->point_product->product_id)
            ->get();

        /**
         * reset marketing point in sales order detail
         * including marketing point was inside log
         * and outside log worker point marketing.
         *
         */
        $sales_order_detail_reset = $this->sales_order_detail->query()
            ->whereIn("sales_order_id", $sales_order_detail->pluck("sales_order_id")->toArray())
            ->where(function ($QQQ) {
                return $QQQ
                    ->whereHas("sales_order", function ($QQQ) {
                        return $QQQ
                            ->whereHas("LogWorkerSalesPoint")
                            ->orWhereHas("logWorkerPointMarketing")
                            ->orWhereDoesntHave("LogWorkerSalesPoint");
                    });
            })
            ->update([
                "marketing_point" => 0,
            ]);

        /**
         * delete log sales point
         */
        $log_sales_point = DB::table('log_worker_sales_points')
            ->whereIn("sales_order_id", $sales_order_detail->pluck("sales_order_id")->toArray())
            ->delete();

        /**
         * delete log worker point marketing
         */
        $log_worker_point_marketing = DB::table('log_worker_point_marketing')
            ->whereIn("sales_order_id", $sales_order_detail->pluck("sales_order_id")->toArray())
            ->delete();

        /**
         * delete log active point marketing
         */
        $log_worker_point_marketing_active = DB::table('log_worker_point_marketing_active')
            ->whereIn("sales_order_id", $sales_order_detail->pluck("sales_order_id")->toArray())
            ->delete();

        /**
         * sales order to reclculate its point
         */
        $sales_order_ids = $sales_order_detail
            ->map(function ($order_detail) {
                return $order_detail->sales_order_id;
            })
            ->unique();

        /**
         * update marketing points associated with this product
         */
        $personel_ids = $sales_order_detail
            ->map(function ($order_detail) {
                return $order_detail->sales_order->personel_id;
            })
            ->unique()
            ->reject(fn($personel_id) => !$personel_id)
            ->each(function ($personel_id) use ($event, $sales_order_ids) {

                /* update marketing point per product */
                $this->recalcultePointMarketingPerProduct($personel_id, $event->point_product->year, $sales_order_ids);

                /* calculate point marketing total */
                $point_total = $this->recalcultePointMarketingTotal($personel_id, $event->point_product->year);

                /* calculate point marketing active */
                $point_marketing_active = $this->recalcultePointMarketingActive($personel_id, $event->point_product->year);
            });

        return $sales_order_ids;
    }
}