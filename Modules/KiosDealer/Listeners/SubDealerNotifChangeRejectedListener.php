<?php

namespace Modules\KiosDealer\Listeners;

use App\Traits\ChildrenList;
use Carbon\Carbon;
use Modules\Authentication\Entities\User;
use Modules\KiosDealer\Events\SubDealerNotifChangeRejectedEvent;
use Modules\KiosDealer\Notifications\SubDealerMarketingSubmission;

class SubDealerNotifChangeRejectedListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */

    use ChildrenList;

    public function __construct()
    {
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(SubDealerNotifChangeRejectedEvent $event)
    {
        /**
         * prize will add to budget plan if there
         * does not exist in budget plan
         */
        $details = [
            'personel_id' => $event->sub_dealer_temp->personel_id,
            'notified_feature' => "sub_dealer",
            'notification_text' => "Perubahan data " . $event->sub_dealer_temp->name . " ditolak",
            'mobile_link' => "/DetailSubDealerTemp",
            'desktop_link' => "/marketing-staff/sub-dealer",
            'data_id' => $event->sub_dealer_temp->id,
            'expired_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta'),
            'as_marketing' => true
        ];

        $member = User::withTrashed()->where("personel_id", $event->sub_dealer_temp->submited_by)->first();

        if ($member) {
            $member->notify(new SubDealerMarketingSubmission($details));

            $notification = $member->notifications->first();
            $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
            $notification->notification_marketing_group_id = "6";
            $notification->notified_feature = "sub_dealer";
            $notification->notification_text = "Perubahan data " . $event->sub_dealer_temp->name . " ditolak";
            $notification->mobile_link = "/DetailSubDealerTemp";
            $notification->desktop_link = "/marketing-staff/data-dealer";
            $notification->as_marketing = true;
            $notification->status = "change rejected";
            $notification->data_id = $event->sub_dealer_temp->id;

            $notification->save();
        }

        $marketing_supervisor = $this->parentPersonel($event->sub_dealer_temp->personel_id);
        foreach ($marketing_supervisor as $key => $value) {
            $member = User::withTrashed()->where("personel_id", $value)->first();
            if ($member) {
                $member->notify(new SubDealerMarketingSubmission($details));

                $notification = $member->notifications->first();
                $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
                $notification->notification_marketing_group_id = "6";
                $notification->notified_feature = "sub_dealer";
                $notification->notification_text = "Perubahan data " . $event->sub_dealer_temp->name . " ditolak";
                $notification->mobile_link = "/ListSubDealerTemp";
                $notification->desktop_link = "/marketing-staff/data-dealer";
                $notification->as_marketing = false;
                $notification->status = "change rejected";
                $notification->personel_id = $event->sub_dealer_temp->personel_id;
                $notification->data_id = $event->sub_dealer_temp->id;

                $notification->save();
            }
        }
    }
}
