<?php

namespace Modules\SalesOrder\Listeners;

use App\Traits\MarketingPoint;
use Illuminate\Support\Facades\DB;
use Modules\PointMarketing\Entities\PointMarketing;
use Modules\SalesOrder\Events\DeletedSalesOrderEvent;

class MarketingPointWhenDeletedSalesOrderListener
{
    use MarketingPoint;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(DeletedSalesOrderEvent $event)
    {
        $point_marketing_populated = $this->populatemarketingPointFromSOD($event->sales_order->sales_order_detail);

        /**
         * reduce marketing point
         */
        collect($point_marketing_populated)->each(function ($marketing) {
            $marketing_point = PointMarketing::query()
                ->where("personel_id", $marketing["personel_id"])
                ->where("year", $marketing["year"])
                ->where("status", "not_redeemed")
                ->first();

            $marketing_point_update = DB::table('point_marketings')
                ->where("personel_id", $marketing["personel_id"])
                ->where("year", $marketing["year"])
                ->where("status", "not_redeemed")
                ->update([
                    "marketing_point_total" => ($marketing_point ? $marketing_point->marketing_point_total - $marketing["point_total_reduced"] : 0),
                    "marketing_point_active" => ($marketing_point ? $marketing_point->marketing_point_active - $marketing["point_active_reduced"] : 0),
                    "marketing_point_redeemable" => ($marketing_point ? $marketing_point->marketing_point_active - $marketing["point_active_reduced"] + $marketing_point->marketing_point_adjustment : 0),
                ]);
        });

        return $point_marketing_populated;
    }
}
