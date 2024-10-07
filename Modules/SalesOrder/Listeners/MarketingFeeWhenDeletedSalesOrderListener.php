<?php

namespace Modules\SalesOrder\Listeners;

use App\Traits\MarketingFee as MarketingFeeTrait;
use Illuminate\Support\Facades\DB;
use Modules\Personel\Entities\MarketingFee;
use Modules\SalesOrderV2\Entities\FeeSharing;
use Modules\SalesOrder\Events\DeletedSalesOrderEvent;

class MarketingFeeWhenDeletedSalesOrderListener
{
    use MarketingFeeTrait;

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
        /**
         * get fee sharing from
         * this sales order
         */
        $fee_sharings = FeeSharing::query()
            ->with([
                "salesOrder" => function ($QQQ) {
                    return $QQQ->with([
                        "invoice" => function ($QQQ) {
                            return $QQQ->with("allPayment");
                        },
                    ]);
                },
                "logMarketingFeeCounter" => function ($QQQ) {
                    return $QQQ->where("type", "reguler");
                },
            ])
            ->where("fee_sharings.sales_order_id", $event->sales_order->id)
            ->leftJoin("sales_orders as s", "s.id", "=", "fee_sharings.sales_order_id")
            ->leftJoin("invoices as i", "i.sales_order_id", "=", "s.id")
            ->select("fee_sharings.*", DB::raw("QUARTER(if(s.type = 2, s.date, i.created_at)) as quarter"))
            ->groupBy("fee_sharings.id")
            ->get();

        /**
         * delete fee sharing from
         * this sales order
         */
        FeeSharing::query()
            ->where("sales_order_id", $event->sales_order->id)
            ->delete();

        /**
         * populate fee to marketing
         * and reduce it
         */
        $marketing_fee_reduction = $this->populateMarketingFeeFromFeeSharing($fee_sharings);

        /**
         * update marketing fee
         */
        collect($marketing_fee_reduction)->each(function ($marketing) {
            $marketing_fee = MarketingFee::query()
                ->where("personel_id", $marketing["personel_id"])
                ->where("year", $marketing["year"])
                ->where("quarter", $marketing["quarter"])
                ->first();

            $marketing_fee_update = DB::table('marketing_fee')
                ->where("personel_id", $marketing["personel_id"])
                ->where("year", $marketing["year"])
                ->where("quarter", $marketing["quarter"])
                ->update([
                    "fee_reguler_total" => $marketing_fee->fee_reguler_total - $marketing["fee_reguler_total"],
                    "fee_reguler_settle" => $marketing_fee->fee_reguler_settle - $marketing["fee_reguler_settle"],
                ]);
        });

        /**
         * delete marketing fee counter to
         * recalculate fee target
         */
        DB::table('log_marketing_fee_counter')
            ->where("sales_order_id", $event->sales_order->id)
            ->where("type", "target")
            ->delete();

        return $marketing_fee_reduction;
    }
}
