<?php

namespace Modules\KiosDealer\Listeners;

use App\Traits\ChildrenList;
use Carbon\Carbon;
use Modules\Authentication\Entities\User;
use Modules\KiosDealer\Events\SubDealerNotifAcceptedEvent;
use Modules\KiosDealer\Notifications\DealerMarketingSubmission;
use Modules\KiosDealer\Notifications\SubDealerMarketingSubmission;

class SubDealerNotifAcceptedListener
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
    public function handle(SubDealerNotifAcceptedEvent $event)
    {
        /**
         * prize will add to budget plan if there
         * does not exist in budget plan
         */

        $details = [
            'personel_id' => $event->sub_dealer->personel_id,
            'notified_feature' => "sub_dealer",
            'notification_text' => "Pengajuan data " . $event->sub_dealer->name . " disetujui",
            'mobile_link' => "/SubDealerInfo",
            'desktop_link' => "/marketing-staff/sub-dealer",
            'data_id' => $event->sub_dealer->id,
            'expired_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta'),
            'as_marketing' => true
        ];

        $member = User::withTrashed()->where("personel_id", $event->sub_dealer->subDealerTemp->submited_by)->first();

        if ($member) {
            $member->notify(new SubDealerMarketingSubmission($details));

            $notification = $member->notifications->first();
            $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
            $notification->notification_marketing_group_id = "6";
            $notification->notified_feature = "sub_dealer";
            $notification->notification_text = "Pengajuan data " . $event->sub_dealer->name . " disetujui";
            $notification->mobile_link = "/SubDealerInfo";
            $notification->desktop_link = "/marketing-staff/data-dealer";
            $notification->as_marketing = true;
            $notification->status = "accepted";
            $notification->data_id = $event->sub_dealer->id;

            $notification->save();
        }

        $marketing_supervisor = $this->parentPersonel($event->sub_dealer->personel_id);
        foreach ($marketing_supervisor as $key => $value) {
            $member = User::withTrashed()->where("personel_id", $value)->first();
            if ($member) {
                $member->notify(new DealerMarketingSubmission($details));

                $notification = $member->notifications->first();
                $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
                $notification->notification_marketing_group_id = "6";
                $notification->notified_feature = "sub_dealer";
                $notification->notification_text = "Pengajuan data " . $event->sub_dealer->name . " disetujui";
                $notification->mobile_link = "/SubDealerInfo";
                $notification->desktop_link = "/marketing-staff/data-dealer";
                $notification->as_marketing = false;
                $notification->status = "accepted";
                $notification->personel_id = $event->sub_dealer->personel_id;
                $notification->data_id = $event->sub_dealer->id;

                $notification->save();
            }
        }
    }
}
