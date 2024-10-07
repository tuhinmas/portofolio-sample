<?php

namespace Modules\Invoice\Listeners;

use App\Traits\DistributorStock;
use App\Traits\SalesOrderOriginTrait;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\SalesOrder\Entities\SalesOrderOrigin;
use Modules\SalesOrder\Entities\LogSalesOrderOrigin;
use Modules\Invoice\Events\SalesOrderOriginDirectGeneratorEvent;

class SalesOrderOriginDirectGeneratorListener
{
    use SalesOrderOriginTrait;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(SalesOrderOriginDirectGeneratorEvent $event)
    {
        $active_contract = $this->distributorActiveContract($event->invoice->salesOrder->store_id);
        
        if ($active_contract) {
            
            /* generate origin */
            $this->generateSalesOrderOrigin($event->invoice->salesOrder);

            return "origin created";
        }
        return "no contract found"; 
    }
}
