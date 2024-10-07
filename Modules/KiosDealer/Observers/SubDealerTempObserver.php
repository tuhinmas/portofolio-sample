<?php

namespace Modules\KiosDealer\Observers;

use Modules\KiosDealer\Entities\SubDealerTemp;
use Modules\KiosDealer\Jobs\SubDealerTempNotificationJob;

class SubDealerTempObserver
{
    public function updated(SubDealerTemp $subDealerTemp)
    {
        $oldStatus = $subDealerTemp->getOriginal('status'); 
        $newStatus = $subDealerTemp->status;
        if ($oldStatus != $newStatus) {
            SubDealerTempNotificationJob::dispatch($subDealerTemp)->onConnection("database");
        }
    }

}
