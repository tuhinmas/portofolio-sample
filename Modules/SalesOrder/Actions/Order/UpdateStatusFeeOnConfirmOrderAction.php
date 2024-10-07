<?php

namespace Modules\SalesOrder\Actions\Order;

use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\SalesOrder\Actions\Order\GetStatusFeeForOrderDependSuggestionAction;
use Modules\SalesOrder\Entities\SalesOrderStatusFeeShould;

class UpdateStatusFeeOnConfirmOrderAction
{
    public function __invoke($sales_order)
    {
        $status_fee_id = (new GetStatusFeeForOrderDependSuggestionAction)($sales_order);

        SalesOrderStatusFeeShould::updateOrCreate([
            "sales_order_id" => $sales_order->id,
        ], [
            "status_fee_id" => $status_fee_id,
            "confirmed_at" => confirmation_time($sales_order),
        ]);

        $sales_order->status_fee_id = $status_fee_id;
        $sales_order->save();

        switch ($sales_order->model) {
            case '1':
                $dealer = Dealer::query()
                    ->where("id", $sales_order->store_id)
                    ->first();

                $dealer->status_fee = $status_fee_id;
                $dealer->save();
                break;

            case '2':
                $sub_dealer = SubDealer::query()
                    ->where("id", $sales_order->store_id)
                    ->first();

                $sub_dealer->status_fee = $status_fee_id;
                $sub_dealer->save();
                break;

            default:
                break;
        }
    }
}
