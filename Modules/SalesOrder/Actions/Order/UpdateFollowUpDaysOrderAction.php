<?php

namespace Modules\SalesOrder\Actions\Order;

use Modules\KiosDealer\Actions\Order\GetLastOrderDealerAction;
use Modules\KiosDealer\Entities\Dealer;
use Modules\SalesOrderV2\Entities\SalesOrderV2;
use Modules\SalesOrder\Entities\SalesOrder;

class UpdateFollowUpDaysOrderAction
{
    public function __invoke($sales_order)
    {

        if (in_array($sales_order->sales_mode, ["office", "follow_up"])) {

            /* get last order of dealer */
            $last_order = (new GetLastOrderDealerAction)($sales_order, now()->format("Y-m-d H:i:s"));
            $last_order_time = now()->startOfDay();

            if ($last_order) {
                $last_order_time = confirmation_time($last_order)->startOfDay();
            }

            /**
             * if dealer doect not have order at all, then follow up days
             * is from it's created yo current days
             */
            else {
                $dealer = Dealer::withTrashed()->findOrFail($sales_order->store_id);
                $last_order_time = $dealer->created_at->startOfDay();
            }

            $follow_up_days = $last_order_time->diffInDays(now(), false);

            $sales_order->follow_up_days = $follow_up_days;
            $sales_order->follow_up_days_updated = true;
            $sales_order->save();

            /**
             * NEW RULES since 2024-09-10
             * follow up days in same day will count as equal as other follow up order,
             * e.g first order as follow up is 78 days, in same day support follow
             * up to same dealer, second order will have 78 days follow up days
             * even there is order by marketing in the middle
             */
            self::zeroFollowDaysHandle($sales_order, $follow_up_days);
        }

        return $sales_order;
    }

    public function zeroFollowDaysHandle(SalesOrder | SalesOrderV2 $sales_order, int $follow_up_days)
    {

        if ($follow_up_days == 0) {
            $considere_indirect_column = considere_indirect_column();
            $considere_direct_column = considere_direct_column();

            $order_follow_up_same_day = SalesOrder::query()
                ->where("id", "!=", $sales_order->id)
                ->where("store_id", $sales_order->store_id)
                ->where("model", $sales_order->model)
                ->where("follow_up_days", ">", "0")
                ->consideredOrder()
                ->where(function ($QQQ) use ($considere_direct_column, $considere_indirect_column) {
                    return $QQQ
                        ->where(function ($QQQ) use ($considere_indirect_column) {
                            $QQQ
                                ->where("type", "2")
                                ->whereDate("$considere_indirect_column", now()->format("Y-m-d"));
                        })
                        ->orWhere(function ($QQQ) use ($considere_direct_column) {
                            $QQQ
                                ->where("type", "1")
                                ->whereHas("invoice", function ($QQQ) use ($considere_direct_column) {
                                    $QQQ->whereDate("$considere_direct_column", now()->format("Y-m-d"));
                                });
                        });
                })
                ->orderBy("order_number", "desc")
                ->first();

            if ($order_follow_up_same_day) {
                $sales_order->follow_up_days = $order_follow_up_same_day->follow_up_days;
                $sales_order->save();
            }
        }

        return $sales_order;
    }
}
