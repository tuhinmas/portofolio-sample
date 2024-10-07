<?php

namespace Modules\SalesOrder\Actions\Order\Origin;

use Modules\Distributor\Actions\GetDistributorActiveContractAction;
use Modules\SalesOrder\Actions\Order\Origin\GenerateSalesOriginFromOrderDetailAction;
use Modules\SalesOrder\Entities\SalesOrderDetail;

/**
 * generate direct and isdirect sales origin
 */
class GenerateSalesOriginFromOrderAction
{
    public function __invoke($sales_order)
    {
        $distributor_id = $sales_order->type == "2" ? $sales_order->distributor_id : $sales_order->store_id;

        $active_contract = (new GetDistributorActiveContractAction)($distributor_id, confirmation_time($sales_order)->format("Y-m-d"));
        if (!$active_contract) {
            return "contract not found";
        }

        SalesOrderDetail::query()
            ->where("sales_order_id", $sales_order->id)
            ->whereHas("salesOrder", function ($QQQ) {
                return $QQQ->consideredOrder();
            })
            ->get()
            ->each(function ($order_detail) use ($active_contract) {
                app(GenerateSalesOriginFromOrderDetailAction::class)($active_contract, $order_detail);
            });
    }
}
