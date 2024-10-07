<?php

namespace Modules\KiosDealer\Listeners;

use Modules\KiosDealer\Entities\Store;
use Modules\KiosDealer\Entities\SubDealer;

class SubDealerRollbackToAcceptedListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        protected SubDealer $sub_dealer,
        protected Store $store,
    ) {

    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        if ($event->dealer_temp->sub_dealer_id) {
            $sub_dealer = $this->sub_dealer->find($event->dealer_temp->sub_dealer_id);
            if ($sub_dealer) {
                $sub_dealer->status = "accepted";
                $sub_dealer->save();
            }
        }

        if ($event->dealer_temp->store_id) {
            $store = $this->store->find($event->dealer_temp->store_id);
            if ($store) {
                $store->status = "accepted";
                $store->save();
            }
        }
    }
}
