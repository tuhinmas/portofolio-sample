<?php

namespace Modules\Invoice\Listeners\CreditMemo;

use Modules\Personel\Entities\LogMarketingFeeCounter;
use Modules\SalesOrder\Entities\FeeSharingSoOrigin;
use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderOrigin;

class OrderRollbackAsNonAffectedReturnListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        protected SalesOrder $sales_order,
        protected SalesOrderOrigin $sales_order_origin,
        protected FeeSharingSoOrigin $fee_sharing_origin,
        protected LogMarketingFeeCounter $log_marketing_fee_counter,
        protected FeeTargetSharingSoOrigin $fee_target_sharing_origin,
    ) {
    }

    /**
     * rollback order as counted fee point after all credit memo was canceled
     * derivatif order from it's destination also rrollback to considered
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $event->sales_orderv2->load([
            "invoice",
        ]);

        $consider_counted_fee_marketing = collect();
        $year_of_return = confirmation_time($event->sales_orderv2)->format("Y");
        $quarter_of_return = confirmation_time($event->sales_orderv2)->quarter;

        /* rollback direct as non affected return */
        /* all dealer / sub dealer order in quartal will considerto get fee*/
        $order_quartal = $this->sales_order->query()
            ->with([
                "dealer",
                "invoice",
                "salesOrderDetail",
                "sales_order_detail",
            ])
            ->where("store_id", $event->sales_orderv2->store_id)
            ->whereNotNull("afftected_by_return")
            ->consideredOrderForReturn()
            ->where(function ($QQQ) use ($year_of_return, $quarter_of_return) {
                return $QQQ
                    ->quartalOrder($year_of_return, $quarter_of_return)
                    ->orwhere(function ($QQQ) use ($year_of_return, $quarter_of_return) {
                        return $QQQ->unconfirmedOrUnSubmitedOrderQuartal($year_of_return, $quarter_of_return);
                    });
            })
            ->orderBy("order_number")
            ->get()
            ->each(function ($order) use ($event) {
                $order->afftected_by_return = null;
                $order->save();
            });

        /**
         * derivatif order from these order also consider to get fee
         */
        $sales_order_origins = $this->sales_order_origin
            ->with([
                "salesOrder",
                "salesOrderDetail",
            ])
            ->where(function ($QQQ) use ($order_quartal) {
                return $QQQ
                    ->whereIn("direct_id", $order_quartal->pluck("id")->toArray())
                    ->orWhereIn("parent_id", $order_quartal->pluck("id")->toArray());
            })
            ->get()
            ->each(function ($origin) use ($event) {
                if ($origin->sales_order_id == $event->sales_orderv2->id) {
                    $origin->update([
                        "is_returned" => false,
                    ]);
                }

                $origin->update([
                    "is_fee_counted" => true,
                ]);
            })
            ->groupBy("sales_order_id")
            ->each(function ($origin_per_order, $sales_order_id) use ($event) {
                $origin_per_order->first()->salesOrder->afftected_by_return = null;
                $origin_per_order->first()->salesOrder->save();
            });

        $consider_counted_fee_marketing->push($order_quartal->pluck("id"));
        $consider_counted_fee_marketing->push($sales_order_origins->pluck("parent_id"));
        $consider_counted_fee_marketing->push($sales_order_origins->pluck("sales_order_id"));

        /**
         * fee sharing marking as non counted
         * fee marketing
         */
        $fee_sharing_origin_update = $this->fee_sharing_origin->query()
            ->whereIn("sales_order_id", $consider_counted_fee_marketing->flatten()->toArray())
            ->get()
            ->each(function ($fee_origin) {
                $fee_origin->is_returned = false;
                $fee_origin->save();

                $this->log_marketing_fee_counter->firstOrCreate([
                    "sales_order_id" => $fee_origin->sales_order_id,
                ]);
            });

        /**
         * fee target sharing marking as non counted fee
         * marketing
         */
        $fee_target_sharing_origin = $this->fee_target_sharing_origin->query()
            ->whereIn("sales_order_id", $consider_counted_fee_marketing->flatten()->toArray())
            ->get()
            ->each(function ($fee_origin) {
                $fee_origin->is_returned = false;
                $fee_origin->save();
            });
    }
}
