<?php

namespace Modules\Invoice\Listeners;

use Modules\Invoice\Entities\AdjustmentStock;
use Modules\Distributor\Entities\DistributorContract;
use Modules\Invoice\Events\AdjusmentStockMatchToDistributorContractEvent;

class AdjusmentStockMatchToDistributorContractListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(DistributorContract $distributor_contract, AdjustmentStock $adjustment_stock)
    {
        $this->distributor_contract = $distributor_contract;
        $this->adjustment_stock = $adjustment_stock;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(AdjusmentStockMatchToDistributorContractEvent $event)
    {
        /**
         * distributor active contract
         */
        $active_distributor_contract = $this->distributor_contract->query()
            ->where("dealer_id", $event->adjustment_stock->dealer_id)
            ->where("contract_start", "<=", now()->format("Y-m-d"))
            ->where("contract_end", ">=", now()->format("Y-m-d"))
            ->first();

        if ($active_distributor_contract) {
            $adjustment_stock =  $this->adjustment_stock->where("id", $event->adjustment_stock->id)->update([
                "contract_id" => $active_distributor_contract->id
            ]);

            return $adjustment_stock;
        }
    }
}
