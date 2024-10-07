<?php

namespace Modules\Invoice\Listeners;

use App\Traits\DistributorStock;
use Carbon\Carbon;
use Modules\DataAcuan\Entities\PointProduct;
use Modules\Invoice\Events\MarketingPointEvent;
use Modules\Personel\Traits\PointMarketingTrait;
use Modules\SalesOrder\Entities\LogWorkerSalesPoint;
use Modules\SalesOrder\Entities\SalesOrderDetail;

class MarketingPointPerProductCalculatorListener
{
    use DistributorStock;
    use PointMarketingTrait;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        LogWorkerSalesPoint $log_worker_sales_point,
        SalesOrderDetail $sales_order_detail,
        PointProduct $point_product,
    ) {
        $this->log_worker_sales_point = $log_worker_sales_point;
        $this->sales_order_detail = $sales_order_detail;
        $this->point_product = $point_product;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(MarketingPointEvent $event)
    {
        return $this->marketingPointPerProductCalculator($event->sales_order);

        /**
         * distributor check, distributor does not
         * get point at all. Only distributor
         * which has active contrack
         */
        if ($event->sales_order->model == "1") {
            if ($event->sales_order->dealer) {
                $active_contract = $this->distributorActiveContract($event->sales_order->dealer->id);

                if ($active_contract) {
                    $event->sales_order->sales_order_detail->each(function ($order_detail) {
                        $detail = $this->sales_order_detail->query()
                            ->where("id", $order_detail->id)
                            ->update([
                                "marketing_point" => 0,
                            ]);
                    });

                    return "distributor pickup found";
                }

            }
        }

        $point_detail = collect();
        $year = confirmation_time($event->sales_order) ? confirmation_time($event->sales_order)->format("Y") : now()->format("Y");

        $event->sales_order->sales_order_detail->each(function ($order_detail) use (&$point_detail, $year) {

            $quantity = $order_detail->quantity;
            $point = 0;

            /* point product this year */
            $point_product = collect($order_detail->pointProductAllYear)->where("year", $year)->where('quantity', '<=', $quantity)->sortByDesc('minimum_quantity')->values();

            collect($point_product)->each(function ($point_per_quantity) use ($year, &$point, $order_detail, &$quantity, &$point_detail) {
                $corresponding_point = floor($quantity / $point_per_quantity->minimum_quantity);
                $modulo = $quantity % $point_per_quantity->minimum_quantity;
                $point += $corresponding_point * $point_per_quantity->point;
                $point_detail->push(collect([
                    "sales_order-detail_id" => $order_detail->id,
                    "product_id" => $order_detail->product_id,
                    "minimum_quantity" => $point_per_quantity->minimum_quantity,
                    "corresponding_point" => $corresponding_point,
                    "modulo" => $modulo,
                    "point" => $point,
                    "year" => $year,
                ]));

                $quantity = $modulo;
            });

            $detail = $this->sales_order_detail->query()
                ->where("id", $order_detail->id)
                ->first();

            $detail->marketing_point = $point;
            $detail->save();

            // dump($detail->marketing_point);
        });

        $log = $this->log_worker_sales_point->updateOrCreate([
            "sales_order_id" => $event->sales_order->id,
            "type" => 2,
        ], [
            "checked_at" => now()]
        );

        return $point_detail;
        return "point marketing counted";
    }
}
