<?php

namespace Modules\KiosDealer\Observers;

use Modules\KiosDealer\Entities\DealerTemp;
use Modules\KiosDealer\Entities\StoreTemp;
use Modules\KiosDealer\Jobs\DealerTempNotificationJob;
use Modules\KiosDealer\Jobs\StoreTempNotificationJob;

class DealerTempObserver
{
    public function updated(DealerTemp $dealerTemp)
    {
        $oldStatus = $dealerTemp->getOriginal('status');
        $newStatus = $dealerTemp->status;
        if ($oldStatus != $newStatus) {
            DealerTempNotificationJob::dispatch($dealerTemp, $oldStatus)->onConnection("sync");
        }
    }

}
