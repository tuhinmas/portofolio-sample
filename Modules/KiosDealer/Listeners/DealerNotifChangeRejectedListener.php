<?php

namespace Modules\KiosDealer\Listeners;

use App\Actions\Notifications\OneSignalPushNotificationAction;
use App\Models\UserDevice;
use App\Traits\ChildrenList;
use Carbon\Carbon;
use Modules\Authentication\Entities\User;
use Modules\KiosDealer\Events\DealerNotifAcceptedEvent;
use Modules\KiosDealer\Events\DealerNotifChangeRejectedEvent;
use Modules\KiosDealer\Notifications\DealerMarketingSubmission;
use Modules\KiosDealer\Notifications\DealerSubmission;
use Modules\SalesOrderV2\Notifications\SalesOrderIndirectSubmission;

class DealerNotifChangeRejectedListener
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
    public function handle(DealerNotifChangeRejectedEvent $event)
    {
        /**
         * prize will add to budget plan if there
         * does not exist in budget plan
         */

        $details = [
            'personel_id' => $event->dealer_temp->personel_id,
            'notified_feature' => "dealer",
            'notification_text' => "Perubahan data " . $event->dealer_temp->name . " ditolak",
            'mobile_link' => "/DetailDealerTemp",
            'desktop_link' => "/marketing-staff/detail-dealer/",
            'data_id' => $event->dealer_temp->id,
            'expired_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta'),
            'as_marketing' => true
        ];

        $memberSubmittedBy = User::withTrashed()->where("personel_id", $event->dealer_temp->submited_by)->first();

        if ($memberSubmittedBy) {
            $notification = $memberSubmittedBy->notifications->first();
            $memberSubmittedBy->notify(new DealerMarketingSubmission($details));        


            if ($notification) {
                $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
                $notification->notification_marketing_group_id = "5";
                $notification->notified_feature = "dealer";
                $notification->notification_text = "Perubahan data " . $event->dealer_temp->name . " ditolak";
                $notification->mobile_link = "/DetailDealerTemp";
                $notification->desktop_link = "/marketing-staff/detail-dealer";
                $notification->as_marketing = true;
                $notification->status = "change rejected";
                $notification->data_id = $event->dealer_temp->id;
                $notification->personel_id = $event->dealer_temp->submited_by;
                $notification->save();
            }
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
                $notification->notification_text = "Perubahan data " . $event->dealer_temp->name . " ditolak";
                $notification->mobile_link = "/DetailDealerTemp";
                $notification->desktop_link = "/marketing-staff/data-dealer";
                $notification->as_marketing = false;
                $notification->status = "change rejected";
                $notification->data_id = $event->dealer_temp->id;
                $notification->personel_id = $event->dealer_temp->personel_id;
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
            ->whereHas('user.personel', function ($q) use ($event) {
                return $q->where('id', $event->dealer_temp->personel_id);
            })
            ->where('is_active', true)
            ->get()
            ->each(function ($player) use ($event) {
                $oneData = [
                    "player_id" => $player->os_player_id,
                    "subtitle" => "Perubahan data " . $event->dealer_temp->name . " ditolak",
                    "data_id" => $event->dealer_temp->id,
                    "message" => "Perubahan data " . $event->dealer_temp->name . " ditolak",
                    "mobile_link" => "/DetailDealerTemp",
                    "notification" => "Perubahan data " . $event->dealer_temp->name . " ditolak",
                    "contents" => [
                        "en" => "Perubahan data " . $event->dealer_temp->name . " ditolak",
                        "in" => "Perubahan data " . $event->dealer_temp->name . " ditolak",
                    ],
                    "menu" => "Dealer",
                    "is_supervisor" => false,
                ];
                return (new OneSignalPushNotificationAction)->execute($oneData);
            });
    }
}
