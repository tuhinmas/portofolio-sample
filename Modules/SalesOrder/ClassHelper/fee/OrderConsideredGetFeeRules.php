<?php

namespace Modules\SalesOrder\ClassHelper\fee;

use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\SalesOrder\Entities\LogWorkerSalesFee;
use Modules\Distributor\Entities\DistributorContract;

/**
 * order check to consider get fee or not
 */
class OrderConsideredGetFeeRules
{

    /**
     * active distributor according nota / proforma date
     * will not get fee at all, or order as office
     * return order or affected from retrun still 
     * get fee and calculate for fee reguler 
     * total only
     *
     * @param [object order] $sales_order
     * @param DistributorContract|null $distributor_active_contract
     * @return boolean
     */
    public function isOrderConsideredToGetFee($sales_order, DistributorContract $distributor_active_contract = null): bool
    {
        $passed = true;
        switch (true) {
            case $sales_order->is_office:
                $passed = false;
                break;

            case $sales_order->model == "1" && $distributor_active_contract:
                $passed = false;
                break;

            default:
                break;
        }

        if (!$passed) {
            SalesOrderDetail::query()
                ->where("sales_order_id", $sales_order->id)
                ->update([
                    "marketing_fee" => 0,
                    "marketing_fee_reguler" => 0,
                ]);
        }

        LogWorkerSalesFee::firstOrCreate([
            "sales_order_id" => $sales_order->id,
        ], [
            "type" => $sales_order->type,
            "checked_at" => now(),
        ]);

        return $passed;
    }
}
