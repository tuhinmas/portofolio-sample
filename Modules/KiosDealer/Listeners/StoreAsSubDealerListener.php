<?php

namespace Modules\KiosDealer\Listeners;

use Carbon\Carbon;
use Modules\KiosDealer\Events\SubDealerRegisteredAsDealerInContestEvent;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrderV2\Entities\SalesOrderV2;

class StoreAsSubDealerListener
{


    /**
     * Create the event listener.
     *
     * @return void
     */

    protected $salesOrderV2;

    public function __construct(SalesOrderV2 $salesOrderV2)
    {
        $this->sales_order = $salesOrderV2;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(SubDealerRegisteredAsDealerInContestEvent $event)
    {

        // purpose pengen tahu riwayat dealer kui pas dadi sub dealer.

        // Tambah kolom untuk riwayat sebelum menjadi dealer, pindahkan id di store_id ke kolom baru, store_as_sub_dealer
        $this->sales_order->query()->where("store_id", $event->sub_dealer->id)
            ->update(
                [
                    'store_as_sub_dealer' => $event->sub_dealer->id,
                    'model' => "1"
                ]
            );

        // store_id saat ini diganti/replace dengan $model->dealer_id
        $this->sales_order->query()->where("store_as_sub_dealer", $event->sub_dealer->id)
            ->update(['store_id' => $event->sub_dealer->dealer_id]);
    }
}
