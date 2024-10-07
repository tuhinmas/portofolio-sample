<?php

namespace Modules\Invoice\Listeners;

use Modules\Contest\Traits\ContestPointTrait;
use Modules\Contest\Entities\ContestParticipant;
use Modules\Contest\Entities\ContestPointOrigin;
use Modules\Invoice\Events\PaymentOnSettleEvent;
use Modules\Contest\Entities\LogContestPointOrigin;

class ContestActivePointListener
{
    use ContestPointTrait;
    
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(ContestPointOrigin $contest_point_origin, LogContestPointOrigin $log_contest_point_origin, ContestParticipant $contest_participant)
    {
        $this->contest_point_origin = $contest_point_origin;
        $this->log_contest_point_origin = $log_contest_point_origin;
        $this->contest_participant = $contest_participant;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(PaymentOnSettleEvent $event)
    {
        /**
         * update participant
         */
        $contest_participant = $this->activeContractStoreByDate($event->invoice->salesOrderOnly->store_id, confirmation_time($event->invoice->salesOrderOnly));
        
        return $this->contestPointActiveChecker($contest_participant, $event->invoice->salesOrderOnly);
        return $contest_participant;
    }
}
