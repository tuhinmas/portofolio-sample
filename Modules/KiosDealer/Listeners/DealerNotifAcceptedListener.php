<?php

namespace Modules\KiosDealer\Listeners;

use App\Actions\Notifications\OneSignalPushNotificationAction;
use App\Models\UserDevice;
use App\Traits\ChildrenList;
use Carbon\Carbon;
use Modules\Authentication\Entities\User;
use Modules\KiosDealer\Entities\DealerTemp;
use Modules\KiosDealer\Events\DealerNotifAcceptedEvent;
use Modules\KiosDealer\Notifications\DealerMarketingSubmission;
use Modules\KiosDealer\Notifications\DealerSubmission;
use Modules\SalesOrderV2\Notifications\SalesOrderIndirectSubmission;

class DealerNotifAcceptedListener
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
    public function handle(DealerNotifAcceptedEvent $event)
    {
        /**
         * prize will add to budget plan if there
         * does not exist in budget plan
         */
        $details = [
            'personel_id' => $event->dealer->personel_id,
            'notified_feature' => "dealer",
            'notification_text' => "Pengajuan data " . $event->dealer->name . " disetujui",
            'mobile_link' => "/DealerInfo",
            'desktop_link' => "/marketing-staff/data-dealer",
            'data_id' => $event->dealer->id,
            'expired_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta'),
            'as_marketing' => true
        ];

        $dealerTemp = DealerTemp::where("name", $event->dealer->name)->withTrashed()->first();
        $member = User::withTrashed()->where("personel_id", $dealerTemp->submited_by)->first();

        // dd($member);

        if ($member) {
            $member->notify(new DealerMarketingSubmission($details));

            $notification = $member->notifications->first();
            $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
            $notification->notification_marketing_group_id = "5";
            $notification->notified_feature = "dealer";
            $notification->notification_text = "Pengajuan data " . $event->dealer->name . " disetujui";
            $notification->mobile_link = "/DealerInfo";
            $notification->desktop_link = "/marketing-staff/detail-dealer";
            $notification->as_marketing = true;
            $notification->status = "accepted";
            $notification->personel_id = $dealerTemp->submited_by;
            $notification->data_id = $event->dealer->id;

            $notification->save();
        }

        $marketing_supervisor = $this->parentPersonel($event->dealer->personel_id);
        foreach ($marketing_supervisor as $key => $value) {
            $member = User::withTrashed()->where("personel_id", $value)->first();
            if ($member) {
                $member->notify(new DealerMarketingSubmission($details));

                $notification = $member->notifications->first();
                $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
                $notification->notification_marketing_group_id = "5";
                $notification->notified_feature = "dealer";
                $notification->notification_text = "Pengajuan data " . $event->dealer->name . " disetujui";
                $notification->mobile_link = "/DealerInfo";
                $notification->desktop_link = "/marketing-staff/data-dealer";
                $notification->as_marketing = false;
                $notification->status = "accepted";
                $notification->personel_id = $event->dealer->personel_id;
                $notification->data_id = $event->dealer->id;

                $notification->save();
            }
        }

        // Push Notif
        UserDevice::query()
            ->with([
                'user' => function ($QQQ) {
                    return $QQQ->with(['permissions', 'roles']);
                },
                'user.personel.position',
            ])
            ->whereHas('user', function ($QQQ) {
                return $QQQ->whereHas('personel');
            })
            ->whereHas('user.personel', function ($q) use ($member) {
                return $q->where('id', $member->personel_id);
            })
            ->where('is_active', true)
            ->get()
            ->each(function ($player) use ($event) {
                $oneData = [
                    "player_id" => $player->os_player_id,
                    "subtitle" => "Pengajuan data " . $event->dealer->name . " disetujui",
                    "data_id" => $event->dealer->id,
                    "message" => "Pengajuan data " . $event->dealer->name . " disetujui",
                    "mobile_link" => "/DealerInfo",
                    "notification" => "Pengajuan data " . $event->dealer->name . " disetujui",
                    "contents" => [
                        "en" => "Pengajuan data " . $event->dealer->name . " disetujui",
                        "in" => "Pengajuan data " . $event->dealer->name . " disetujui",
                    ],
                    "menu" => "Dealer",
                    "is_supervisor" => false,
                ];

                // dump($oneData);

                return (new OneSignalPushNotificationAction)->execute($oneData);
            });
    }
}
