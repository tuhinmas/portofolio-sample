<?php

namespace Modules\KiosDealer\Listeners;

use Modules\Contest\Entities\ContestParticipant;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\KiosDealer\Events\SubDealerRegisteredAsDealerInContestEvent;

class SubDealerRegisteredAsDealerReplaceContestParticipantListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        protected SubDealer $sub_dealer,
        protected ContestParticipant $contest_participant,
    ) {
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(SubDealerRegisteredAsDealerInContestEvent $event)
    {
        /**
         * according to new rule dated 2023-07-31
         * all sub dealer contracts will be
         * transferred to dealers
         */
        $active_contest_prticipant_sub_dealer = self::contestParticipantTransfer($event->sub_dealer);

        /* transfer log phone to new dealer */
        self::logPhoneTransfer($event->sub_dealer);

        return $active_contest_prticipant_sub_dealer->pluck("id");
    }

    public function contestParticipantTransfer($sub_dealer)
    {
        return $this->contest_participant->query()
            ->where("sub_dealer_id", $sub_dealer->id)
            ->get()
            ->each(function ($contract) use ($sub_dealer) {
                $contract->dealer_id = $sub_dealer->dealer_id;
                $contract->save();
            });
    }

    /**
     * replace log phone from sub dealer to dealer if exist
     *
     * @param [type] $sub_dealer
     * @return void
     */
    public function logPhoneTransfer($sub_dealer)
    {
        $sub_dealer->load("logPhones");
        if (!$sub_dealer->logPhones->isEmpty()) {
            $sub_dealer
                ->logPhones
                ->toQuery()
                ->update([
                    "model_id" => $sub_dealer->dealer_id,
                    "model" => Dealer::class,
                ]);
        }
    }
}
