<?php

namespace Modules\KiosDealer\Listeners;

use App\Actions\Notifications\OneSignalPushNotificationAction;
use App\Models\UserDevice;
use App\Traits\ChildrenList;
use Carbon\Carbon;
use Ladumor\OneSignal\OneSignal;
use Modules\Authentication\Entities\User;
use Modules\KiosDealer\Events\DealerFilledRejectedEvent;
use Modules\KiosDealer\Notifications\DealerMarketingSubmission;

class DealerNotifFilledRejectedListener
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
    public function handle(DealerFilledRejectedEvent $event)
    {
        /**
         * 
         * 
         */

        $details = [
            'personel_id' => $event->dealer_temp->personel_id,
            'notified_feature' => "dealer",
            'notification_text' => "Pengajuan data " . $event->dealer_temp->name . " ditolak",
            'mobile_link' => "/DetailDealerTemp",
            'desktop_link' => "/marketing-staff/data-dealer",
            'data_id' => $event->dealer_temp->id,
            'expired_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta'),
            'as_marketing' => true
        ];

        $member = User::withTrashed()->where("personel_id", $event->dealer_temp->submited_by)->first();

        if ($member) {
            $member->notify(new DealerMarketingSubmission($details));

            $notification = $member->notifications->first();
            // if ($notification) {
            $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
            $notification->notification_marketing_group_id = "5";
            $notification->notified_feature = "dealer";
            $notification->notification_text = "Pengajuan data " . $event->dealer_temp->name . " ditolak";
            $notification->mobile_link = "/DetailDealerTemp";
            $notification->desktop_link = "/marketing-staff/data-dealer";
            $notification->as_marketing = true;
            $notification->status = "filed rejected";
            $notification->data_id = $event->dealer_temp->id;
            $notification->personel_id = $event->dealer_temp->submited_by;
            // dd($notification);
            $notification->save();
            // }
        }

        $marketing_supervisor = $this->parentPersonel($event->dealer_temp->personel_id);
        foreach ($marketing_supervisor as $key => $value) {
            $member = User::withTrashed()->where("personel_id", $value)->first();
            if ($member) {
                $member->notify(new DealerMarketingSubmission($details));

                $notification = $member->notifications->first();
                $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
                $notification->notification_marketing_group_id = "5";
                $notification->notified_feature = "dealer";
                $notification->notification_text = "Pengajuan data " . $event->dealer_temp->name . " ditolak";
                $notification->mobile_link = "/DetailDealerTemp";
                $notification->desktop_link = "/marketing-staff/data-dealer";
                $notification->as_marketing = false;
                $notification->status = "filed rejected";
                $notification->data_id = $event->dealer_temp->id;
                $notification->personel_id = $event->dealer_temp->personel_id;
                $notification->save();
            }
        }

        $users = User::with(['userDevices'])
            ->withTrashed()
            ->where("personel_id", $member->personel_id)
            ->get();

        $userDevices = $users->map(function ($q) {
            return $q->userDevices->map(function ($q) {
                return $q->os_player_id;
            })->toArray();
        })->flatten()->toArray();

        $textNotif = "Pengajuan data " . $event->dealer_temp->name . " ditolak";
        $fields = [
            "include_player_ids" => $userDevices,
            "data" => [
                "subtitle" => "Pengajuan Dealer Baru Tolak",
                "menu" => "Dealer",
                "data_id" => $event->dealer_temp->id,
                "mobile_link" => "/DetailDealerTemp",
                "desktop_link" => "/marketing-staff/data-dealer",
                "notification" => $textNotif,
                "is_supervisor" => false,
            ],
            "contents" => [
                "en" => $textNotif,
                "in" => $textNotif,
            ],
            "recipients" => 1
        ];

        return OneSignal::sendPush($fields, $textNotif);

        // UserDevice::query()
        //     ->with([
        //         'user' => function ($QQQ) {
        //             return $QQQ->with(['permissions', 'roles']);
        //         },
        //         'user.personel.position',
        //     ])
        //     ->whereHas('user', function ($QQQ) {
        //         return $QQQ->whereHas('personel');
        //     })
        //     ->whereHas('user.personel', function ($q) use ($member) {
        //         return $q->where('id', $member->personel_id);
        //     })
        //     ->where('is_active', true)
        //     ->get()
        //     ->each(function ($player) use ($event) {
        //         $oneData = [
        //             "player_id" => $player->os_player_id,
        //             "subtitle" => "Pengajuan data " . $event->dealer_temp->name . " ditolak",
        //             "data_id" => $event->dealer_temp->id,
        //             "message" => "Pengajuan data " . $event->dealer_temp->name . " ditolak",
        //             "mobile_link" => "/DetailDealerTemp",
        //             "notification" => "Pengajuan data " . $event->dealer_temp->name . " ditolak",
        //             "contents" => [
        //                 "en" => "Pengajuan data " . $event->dealer_temp->name . " ditolak",
        //                 "in" => "Pengajuan data " . $event->dealer_temp->name . " ditolak",
        //             ],
        //             "menu" => "Dealer",
        //             "is_supervisor" => false,
        //         ];

                // dump($oneData);

                // return (new OneSignalPushNotificationAction)->execute($oneData);
            // });
    }
}
