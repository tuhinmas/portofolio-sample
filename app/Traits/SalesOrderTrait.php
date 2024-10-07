<?php

namespace App\Traits;

/**
 *
 */
trait SalesOrderTrait
{
    public function orderInsideContractDistributor($order)
    {
        $inside_contract = false;
        if ($order->type == "1" && $order->invoice) {
            $order->dealer?->ditributorContract->each(function ($contract) use ($order, &$inside_contract) {
                if ($order->invoice->created_at >= $contract->contract_start && $order->invoice->created_at <= $contract->contract_end) {
                    $inside_contract = true;
                    return false;
                }
            });
        }

        return $inside_contract;
    }
}
