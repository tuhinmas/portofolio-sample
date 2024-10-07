<?php

namespace Modules\KiosDealer\Observers;

use Modules\KiosDealer\Entities\StoreTemp;
use Modules\KiosDealer\Jobs\StoreTempNotificationJob;

class StoreTempObserver
{
    public function updated(StoreTemp $storeTemp)
    {
        $oldStatus = $storeTemp->getOriginal('status');
        $newStatus = $storeTemp->status;
        if ($oldStatus != $newStatus) {
            StoreTempNotificationJob::dispatch($storeTemp)->onConnection("sync");
        }
    }

}
