<?php

namespace Modules\Invoice\Listeners;

use App\Traits\DistributorStock;
use Modules\Distributor\ClassHelper\DistributorRevoke;
use Modules\Distributor\ClassHelper\DistributorSuspend;
use Modules\Invoice\Events\AdjusmentToOriginEvent;
use Modules\KiosDealerV2\Entities\DistributorProductSuspended;
use Modules\KiosDealerV2\Entities\DistributorSuspended;
use Modules\KiosDealerV2\Events\NotificationOnInsuficientProductStockEvent;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderOrigin;

class PreviousStockCheckingAfterAdjustmentListener
{
    use DistributorStock;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(SalesOrderOrigin $sales_order_origin, SalesOrder $sales_order, DistributorSuspended $distributor_suspended, DistributorProductSuspended $distributor_product_suspended)
    {
        $this->distributor_product_suspended = $distributor_product_suspended;
        $this->distributor_suspended = $distributor_suspended;
        $this->sales_order_origin = $sales_order_origin;
        $this->sales_order = $sales_order;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(AdjusmentToOriginEvent $event)
    {

        /* active contract distributor */
        $active_contract = $this->distributorActiveContract($event->adjustment_stock->dealer_id);
        $current_stock = $this->distributorProductCurrentStockAdjusmentBased($event->adjustment_stock->dealer_id, $event->adjustment_stock->product_id);

        if ($active_contract) {

            /**
             * check stock before adjustment
             */
            if (!$event->adjustment_stock->is_first_stock) {

                /**
                 * stock system before opname date
                 */
                $stock_system = $this->stockSystem($event->adjustment_stock->dealer_id, $event->adjustment_stock->product_id);
                $previous_stock = $this->distributorProductStockPreviousBeforeAdjusment($event->adjustment_stock->dealer_id, $event->adjustment_stock->product_id, $event->adjustment_stock);

                /**
                 * stok system check for pending or not, if stock system
                 * less hen current stock, all distributor sales
                 * will be pending during it's contract
                 */
                if ($stock_system->current_stock_system < $current_stock->current_stock || $current_stock->current_stock < 0) {

                    /**
                     * distributor product will suspended
                     */
                    DistributorSuspend::suspendDistributorProduct($event->adjustment_stock->dealer_id, $event->adjustment_stock->product_id);

                    /**
                     * distributor sales during contract will pending if confirmed
                     * or onhold if submited
                     */
                    DistributorSuspend::pendingOrderFromInsufficientStock($active_contract, $event->adjustment_stock->dealer_id, $event->adjustment_stock->product_id);

                    /* notification if stock minus for supervisor */
                    $notification = NotificationOnInsuficientProductStockEvent::dispatch($event->adjustment_stock->dealer_id, $event->adjustment_stock->product_id, $current_stock);

                } elseif ($stock_system->current_stock_system >= $current_stock->current_stock) {

                    /**
                     * revoke distributor from suspend, basically
                     * if stock was sufficient
                     */
                    DistributorRevoke::revokeDistributorProductSuspend($event->adjustment_stock->dealer_id, $event->adjustment_stock->product_id);

                    /**
                     * revoke distributor sales duting contract
                     * pending to confirmed and onhold to
                     * submited
                     */
                    DistributorRevoke::revokeOrderFromSufficientStock($active_contract, $event->adjustment_stock->dealer_id, $event->adjustment_stock->product_id);
                }

                /**
                 * current self sales check
                 */
                $self_sales = 0;
                $must_return = 0;
                if ($previous_stock->previous_stock > $event->adjustment_stock->real_stock) {

                    $stock_different = $previous_stock->previous_stock - $event->adjustment_stock->real_stock;
                    $self_sales = $previous_stock->previous_stock - $event->adjustment_stock->real_stock;

                    /* self sales in adjustment */
                    $current_adjustment = $event->adjustment_stock;
                    $current_adjustment->self_sales = $self_sales;
                    $current_adjustment->previous_stock = $previous_stock->previous_stock;
                    $current_adjustment->save();
                }

                /**
                 * current self sales check if current stock more than previous stock
                 * all distributor sales in contract period
                 * that match with product opnamed will
                 * be set to pending, and this product
                 * will be suspended, it mean all
                 * purchase to this distributor
                 * will rejected
                 */
                else if ($previous_stock->previous_stock < $event->adjustment_stock->real_stock) {

                    /**
                     * self sales in adjustment
                     */
                    $must_return = $event->adjustment_stock->real_stock - $previous_stock->previous_stock - ($previous_stock->previous_adjustment ? $previous_stock->previous_adjustment->self_sales : 0) > 0
                    ? $event->adjustment_stock->real_stock - $previous_stock->previous_stock - ($previous_stock->previous_adjustment ? $previous_stock->previous_adjustment->self_sales : 0) : null;
                    $self_sales = $event->adjustment_stock->real_stock - $previous_stock->previous_stock - ($previous_stock->previous_adjustment ? $previous_stock->previous_adjustment->self_sales : 0) <= 0 ? ($previous_stock->previous_adjustment ? $previous_stock->previous_adjustment->self_sales : 0) - ($event->adjustment_stock->real_stock - $previous_stock->previous_stock) : null;

                    $current_adjustment = $event->adjustment_stock;
                    $current_adjustment->previous_stock = $previous_stock->previous_stock;
                    $current_adjustment->must_return = $must_return;
                    $current_adjustment->self_sales = $self_sales;
                    $current_adjustment->save();
                }

                /**
                 * if current stock less than previous stock
                 * there should self sales in distributor
                 */
                if ($previous_stock->previous_stock + ($self_sales >= 0 ? ($self_sales != 0 ? $previous_stock->previous_self_sales - $self_sales : 0) : 0) > $event->adjustment_stock->real_stock) {

                    /* sales order origin before opname */
                    $distributor_pickup = $this->sales_order_origin
                        ->with("salesOrderDetail")
                        ->whereHas("salesOrderDetail")
                        ->where("store_id", $event->adjustment_stock->dealer_id)
                        ->where("product_id", $event->adjustment_stock->product_id)
                        ->whereDate("confirmed_at", ">=", $previous_stock->previous_adjustment->opname_date)
                        ->whereHas("salesOrder", function ($QQQ) {
                            return $QQQ->where(function ($QQQ) {
                                return $QQQ->considerOrderStatusForRecap();
                            });
                        })
                        ->get();

                    if ($distributor_pickup->count() > 0) {
                        $origin_update = $distributor_pickup
                            ->sortByDesc("confirmed_at")
                            ->map(function ($origin) use (&$stock_different, $previous_stock) {
                                $origin->stock_different = $stock_different;
                                $origin->previous_stock = $previous_stock->previous_stock;
                                $origin->self_sales = ($stock_different < $origin->stock_ready ? $stock_different : $origin->stock_ready);
                                $origin->stock_ready = $origin->stock_ready - $origin->self_sales;
                                $stock_different -= $origin->self_sales;
                                return $origin->only(["id", "previous_stock", "stock_different", "self_sales", "stock_ready"]);
                            })
                            ->map(function ($origin) {

                                /* update salef sales in origin */
                                $origin = $this->sales_order_origin
                                    ->where("id", $origin["id"])
                                    ->update([
                                        "self_sales" => $origin["self_sales"],
                                        "stock_ready" => $origin["stock_ready"],
                                    ]);

                                return $origin;
                            });
                    }

                    /* self sales in adjustment */
                    $current_adjustment = $event->adjustment_stock;
                }

                /**
                 * if current stock more than previous stock
                 * all distributor sales in contract period
                 * that match with product opnamed will
                 * be set to pending, and this product
                 * will be suspended, it mean all
                 * purchase to this distributor
                 * will rejected
                 */
                else if ($previous_stock->previous_stock + ($self_sales >= 0 ? ($self_sales != 0 ? $previous_stock->previous_self_sales - $self_sales : 0) : 0) < $event->adjustment_stock->real_stock) {

                    if ($active_contract) {

                        $current_adjustment = $event->adjustment_stock;

                        if ($must_return > 0) {

                            $current_adjustment->must_return = $must_return;
                            $is_distributor_pending = true;
                        }
                    }
                }

                return (object) [
                    "previous_stock" => $previous_stock->previous_stock,
                    "previous_stock_according_self_sales" => $previous_stock->previous_stock + ($self_sales >= 0 ? $previous_stock->previous_self_sales - $self_sales : $self_sales),
                    "self_sales" => $self_sales,
                    "must_return" => $must_return,
                    "current_stock" => $current_stock->current_stock,
                ];
            }

            /* first stock, if still minus pending order during contract */
            else {
                if ($current_stock->current_stock < 0) {

                    /**
                     * distributor product will suspended
                     */
                    DistributorSuspend::suspendDistributorProduct($event->adjustment_stock->dealer_id, $event->adjustment_stock->product_id);

                    /**
                     * distributor sales during contract will pending if confirmed
                     * or onhold if submited
                     */
                    DistributorSuspend::pendingOrderFromInsufficientStock($active_contract, $event->adjustment_stock->dealer_id, $event->adjustment_stock->product_id);

                    /* notification if stock minus for supervisor */
                    $notification = NotificationOnInsuficientProductStockEvent::dispatch($event->adjustment_stock->dealer_id, $event->adjustment_stock->product_id, $current_stock);
                }

                return $current_stock;
            }
        } else {
            return "no distributor contract found";
        }
        return 0;
    }
}
