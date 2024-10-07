<?php

namespace App\Traits;

use App\Traits\DistributorStock;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\KiosDealerV2\Entities\DistributorProductSuspended;
use Modules\KiosDealerV2\Entities\DistributorSuspended;
use Modules\SalesOrder\Entities\SalesOrder;

/**
 * distributor suspended
 */
trait DistributorSuspendedTrait
{
    use DistributorStock;

    public function distributorSuspendedCheck($distributor_id)
    {
        DealerV2::findOrFail($distributor_id);

        $distributor_suspended = DistributorSuspended::query()
            ->with([
                "distributorProductSuspended" => function ($QQQ) {
                    return $QQQ->with("product");
                },
            ])
            ->where("dealer_id", $distributor_id)
            ->first();

        if (!$distributor_suspended) {
            return $distributor_suspended;
        }

        $distributor_suspended->distributor_product_suspended = $distributor_suspended->distributorProductSuspended
            ->map(function ($suspended_product) use ($distributor_id) {
                $current_stock = $this->distributorProductCurrentStockAdjusmentBased($distributor_id, $suspended_product->product_id);
                $suspended_product->cuurnet_stock = $current_stock->current_stock;
                return $suspended_product;
            });

        $distributor_suspended->unsetRelation("distributorProductSuspended");

        return $distributor_suspended;
    }

    /**
     * pending order all distributor sales during contract
     * because insuficient product stock
     *
     * @return void
     */
    public function pendingConfirmedOrderDuringContractFromInsufficientStock($active_contract, $distributor_id, $product_id)
    {
        if ($active_contract) {

            /* pending distributor sales duting contract */
            return SalesOrder::query()
                ->distributorSalesDuringContract($distributor_id, $active_contract->contract_start, $active_contract->contract_end)
                ->where("status", "confirmed")
                ->whereHas("salesOrderDetail", function ($QQQ) use ($product_id) {
                    return $QQQ->where("product_id", $product_id);
                })
                ->get()
                ->each(function ($order) {
                    $order->status = "pending";
                    $order->save();
                });
        }
    }

    /**
     * set status indirect to onhold, from submited
     * if there insufficient stock
     *
     * @param [type] $active_contract
     * @param [type] $distributor_id
     * @param [type] $product_id
     * @return void
     */
    public function pendingSubmitedOrderDuringContractFromInsufficientStock($active_contract, $distributor_id, $product_id)
    {

        if ($active_contract) {

            /* pending distributor sales duting contract */
            $distributor_sales_during_contract = SalesOrder::query()
                ->distributorSubmitedSalesDuringContract($distributor_id, $active_contract->contract_start, $active_contract->contract_end)
                ->where("status", "submited")
                ->whereHas("salesOrderDetail", function ($QQQ) use ($product_id) {
                    return $QQQ->where("product_id", $product_id);
                })
                ->get()
                ->each(function ($order) {
                    $order->status = "onhold";
                    $order->save();
                });
        }
    }

    /*
    |-------------------------
    | REVOKE SUSPEND
    |--------------------
     */

    public function revokeProductFromSuspend($distributor_id, $product_id)
    {
        DistributorProductSuspended::query()
            ->whereHas("distributorSuspended", function ($QQQ) use ($distributor_id) {
                return $QQQ->where("dealer_id", $distributor_id);
            })
            ->where("product_id", $product_id)
            ->delete();

        $count_product_suspend = DistributorProductSuspended::query()
            ->whereHas("distributorSuspended", function ($QQQ) use ($distributor_id) {
                return $QQQ->where("dealer_id", $distributor_id);
            })
            ->where("product_id", $product_id)
            ->count();

        if ($count_product_suspend == 0) {
            DistributorSuspended::query()
                ->where("dealer_id", $distributor_id)
                ->get();
        }
    }
}
