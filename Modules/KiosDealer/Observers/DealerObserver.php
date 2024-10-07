<?php

namespace Modules\KiosDealer\Observers;

use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Jobs\DealerNotificationJob;
use Modules\KiosDealerV2\Entities\DealerV2;

class DealerObserver
{
    public function updated(Dealer|DealerV2 $dealer)
    {
        $oldStatus = $dealer->getOriginal('status');
        $newStatus = $dealer->status;
        if ($oldStatus != $newStatus) {
            DealerNotificationJob::dispatch($dealer, $oldStatus)->onConnection("sync");
        }
    }

}
