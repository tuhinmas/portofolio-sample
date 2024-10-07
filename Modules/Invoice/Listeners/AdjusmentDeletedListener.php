<?php

namespace Modules\Invoice\Listeners;

use App\Traits\DistributorStock;
use Modules\Invoice\Events\AdjusmentDeletedEvent;
use Modules\SalesOrder\Entities\SalesOrder;

class AdjusmentDeletedListener
{
    use DistributorStock;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(SalesOrder $sales_order)
    {
        $this->sales_order = $sales_order;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(AdjusmentDeletedEvent $event)
    {
        
        /* active contract */
        $active_contract = $this->distributorActiveContract($event->adjustment_stock->dealer_id);
        
        if ($active_contract) {
            
            /**
             * current stock after adjustment deleted
             */
            $current_stock = $this->distributorProductCurrentStockAdjusmentBased($event->adjustment_stock->dealer_id, $event->adjustment_stock->product_id);
    
            /* stock system */
            $stock_system = $this->stockSystem($event->adjustment_stock->dealer_id, $event->adjustment_stock->product_id);

            if ($current_stock->current_stock >= $stock_system->current_stock_system) {
                return[
                    "current_stock" =>  $current_stock->current_stock,
                    "stock_system" =>  $stock_system->current_stock_system,
                    "message" => "stock system is less then stok rill, all sales from this distributor will pending"
                ];
            }

            /**
             * pending all sales inside contract after pervious adjusment
             * get all distributor sales in contract period
             */
            $sales_in_conatrct_period = $this->sales_order
                ->where("distributor_id", $event->adjustment_stock->dealer_id)
                ->whereDate("created_at", ">=", $active_contract->contract_start)
                ->whereDate("created_at", "<=", $active_contract->contract_end)
                ->where("status", "pending")
                ->whereHas("sales_order_detail", function ($QQQ) use ($event) {
                    return $QQQ
                        ->where("product_id", $event->adjustment_stock->product_id);
                })
                ->update([
                    "status" => "confirmed",
                ]);
    
            return $sales_in_conatrct_period;
        }

        return "no contract found";
    }
}
