<?php

namespace Modules\KiosDealer\Listeners;

use App\Actions\Notifications\OneSignalPushNotificationAction;
use App\Models\UserDevice;
use Carbon\Carbon;
use App\Traits\ChildrenList;
use Modules\Authentication\Entities\User;
use Illuminate\Support\Facades\Notification;
use Modules\KiosDealer\Events\DealerNotifWaitingApprovalEvent;
use Modules\KiosDealer\Notifications\DealerMarketingSubmission;

class DealerNotifWaitingApprovalListener
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
    public function handle(DealerNotifWaitingApprovalEvent $event)
    {
        /**
         * prize will add to budget plan if there
         * does not exist in budget plan
         */
        $details = [
            'notified_feature' => "dealer",
            'notification_text' => "Pengajuan data " . $event->dealer_temp->name . " telah dikonfirmasi",
            'mobile_link' => "/DetailDealerTemp",
            'desktop_link' => "/marketing-staff/indirect-sales-report?id=" . $event->dealer_temp->id,
            'data_id' => $event->dealer_temp->id,
            'expired_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta'),
            'as_marketing' => true,
        ];

        $member = User::withTrashed()->where("personel_id", $event->dealer_temp->submited_by)->first();

        // $memberSubmittedBy = User::withTrashed()->where("personel_id", $event->dealer_temp->submited_by)->first();

        if ($member) {
            $notification = $member->notifications->first();
            $member->notify(new DealerMarketingSubmission($details));        

            if ($notification) {
                $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
                $notification->notification_marketing_group_id = "5";
                $notification->notified_feature = "dealer";
                $notification->notification_text = "Pengajuan data " . $event->dealer_temp->name . " telah dikonfirmasi";
                $notification->mobile_link = "/DetailDealerTemp";
                $notification->desktop_link = "/marketing-staff/detail-dealer/";
                $notification->as_marketing = true;
                $notification->status = "wait approval";
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
                $notification->notification_text = "Pengajuan data " . $event->dealer_temp->name . " telah dikonfirmasi";
                $notification->mobile_link = "/DetailDealerTemp";
                $notification->desktop_link = "/marketing-staff/data-dealer";
                $notification->as_marketing = false;
                $notification->status = "wait approval";
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
            ->whereHas('user', function ($QQQ) {
                return $QQQ->whereHas('personel');
            })
            ->whereHas('user.personel', function($q) use($member){
                return $q->where('id', $member->personel_id);
            })
            ->where('is_active', true)
            ->get()
            ->each(function($player) use($event){
                $oneData = [
                    "player_id" => $player->os_player_id,
                    "subtitle" => "Pengajuan data " . $event->dealer_temp->name . " telah dikonfirmasi",
                    "data_id" => $event->dealer_temp->id,
                    "message" => "Pengajuan data " . $event->dealer_temp->name . " telah dikonfirmasi",
                    "mobile_link" => "/DetailDealerTemp",
                    "notification" => "Pengajuan data " . $event->dealer_temp->name . " telah dikonfirmasi",
                    "contents" => [
                        "en" => "Pengajuan data " . $event->dealer_temp->name . " telah dikonfirmasi",
                        "in" => "Pengajuan data " . $event->dealer_temp->name . " telah dikonfirmasi",
                    ],
                    "menu" => "Dealer",
                    "is_supervisor" => false,
                ];

                // dump($oneData);

                return (new OneSignalPushNotificationAction)->execute($oneData);
            });
    }
}
