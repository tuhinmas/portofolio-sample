<?php

namespace Modules\KiosDealer\Listeners;

use App\Actions\Notifications\OneSignalPushNotificationAction;
use App\Models\UserDevice;
use Carbon\Carbon;
use App\Traits\ChildrenList;
use Modules\Authentication\Entities\User;
use Illuminate\Support\Facades\Notification;
use Modules\KiosDealer\Events\DealerNotifWaitingApprovalDataChangeEvent;
use Modules\KiosDealer\Events\DealerNotifWaitingApprovalEvent;
use Modules\KiosDealer\Notifications\DealerMarketingSubmission;

class DealerNotifWaitingApprovalDataChangeListener
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
    public function handle(DealerNotifWaitingApprovalDataChangeEvent $event)
    {
        /**
         * prize will add to budget plan if there
         * does not exist in budget plan
         */
        $details = [
            'notified_feature' => "dealer",
            'notification_text' => "Perubahan data " . $event->dealer_temp->name . " telah dikonfirmasi",
            'mobile_link' => "/DetailDealerTemp",
            'desktop_link' => "/marketing-staff/indirect-sales-report?id=" . $event->dealer_temp->id,
            'data_id' => $event->dealer_temp->id,
            'expired_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta'),
            'as_marketing' => true,
        ];

        //$member = User::withTrashed()->where("personel_id", $event->dealer_temp->personel_id)->first();

        $memberSubmittedBy = User::withTrashed()->where("personel_id", $event->dealer_temp->submited_by)->first();

        if ($memberSubmittedBy) {
            $notification = $memberSubmittedBy->notifications->first();
            $memberSubmittedBy->notify(new DealerMarketingSubmission($details));        

            if ($notification) {
                $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
                $notification->notification_marketing_group_id = "5";
                $notification->notified_feature = "dealer";
                $notification->notification_text = "Perubahan data " . $event->dealer_temp->name . " telah dikonfirmasi";
                $notification->mobile_link = "/DetailDealerTemp";
                $notification->desktop_link = "/marketing-staff/detail-dealer/";
                $notification->as_marketing = true;
                $notification->status = "wait approval";
                $notification->data_id = $event->dealer_temp->id;
                $notification->personel_id = $event->dealer_temp->submited_by;
                $notification->save();
            }
        }

        
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
            ->whereHas('user.personel', function($q) use($memberSubmittedBy){
                return $q->where('id', $memberSubmittedBy->personel_id);
            })
            ->where('is_active', true)
            ->get()
            ->each(function($player) use($event){
                $oneData = [
                    "player_id" => $player->os_player_id,
                    "subtitle" => "Perubahan data " . $event->dealer_temp->name . " telah dikonfirmasi",
                    "data_id" => $event->dealer_temp->id,
                    "message" => "Perubahan data " . $event->dealer_temp->name . " telah dikonfirmasi",
                    "mobile_link" => "/DetailDealerTemp",
                    "notification" => "Perubahan data " . $event->dealer_temp->name . " telah dikonfirmasi",
                    "contents" => [
                        "en" => "Perubahan data " . $event->dealer_temp->name . " telah dikonfirmasi",
                        "in" => "Perubahan data " . $event->dealer_temp->name . " telah dikonfirmasi",
                    ],
                    "menu" => "Dealer",
                    "is_supervisor" => false,
                ];

                // dump($oneData);

                return (new OneSignalPushNotificationAction)->execute($oneData);
            });
    }
}
