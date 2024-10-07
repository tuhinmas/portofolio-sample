<?php

namespace Modules\Invoice\Listeners;

use App\Traits\DistributorTrait;
use Modules\Contest\Entities\ContestParticipant;
use Modules\Contest\Entities\ContestPointOrigin;
use Modules\Contest\Entities\LogContestPointOrigin;
use Modules\Contest\Traits\ContestPointTrait;
use Modules\Invoice\Events\ContestPointOriginEvent;
use Modules\SalesOrder\Entities\SalesOrderDetail;

class ContestPointOriginGeneratorListener
{
    use DistributorTrait;
    use ContestPointTrait;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        LogContestPointOrigin $log_contest_point_origin,
        ContestPointOrigin $contest_point_origin,
        ContestParticipant $contest_participant,
    ) {
        $this->log_contest_point_origin = $log_contest_point_origin;
        $this->contest_point_origin = $contest_point_origin;
        $this->contest_participant = $contest_participant;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(ContestPointOriginEvent $event)
    {
        if ($event->active_contract) {
            $sales_order_details = SalesOrderDetail::query()
                ->where("sales_order_id", $event->invoice->sales_order_id)
                ->get();

            return $this->contestPointOriginGenerator($event->active_contract, $event->invoice->salesOrder, $sales_order_details);
        } else {
            return "contest participant not found";
        }
    }
}
