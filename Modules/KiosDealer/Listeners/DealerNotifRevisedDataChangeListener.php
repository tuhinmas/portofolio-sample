<?php

namespace Modules\KiosDealer\Listeners;

use App\Actions\Notifications\OneSignalPushNotificationAction;
use App\Models\UserDevice;
use App\Traits\ChildrenList;
use Carbon\Carbon;
use Modules\Authentication\Entities\User;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Events\DealerNotifRevisedDataChangeEvent;
use Modules\KiosDealer\Notifications\DealerMarketingSubmission;

class DealerNotifRevisedDataChangeListener
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
    public function handle(DealerNotifRevisedDataChangeEvent $event)
    {
        /**
         * prize will add to budget plan if there
         * does not exist in budget plan
         */
        $details = [
            'personel_id' => $event->dealerTemp->personel_id,
            'notified_feature' => "dealer",
            'notification_text' => "Perubahan data " . $event->dealerTemp->name . " membutuhkan revisi",
            'mobile_link' => "",
            'desktop_link' => "",
            'data_id' => $event->dealerTemp->id,
            'expired_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta'),
            'as_marketing' => true
        ];

        $member = User::withTrashed()->where("personel_id", $event->dealerTemp->submited_by)->first();
        
        if ($member && $event->dealerTemp->dealer_id) {
            $member->notify(new DealerMarketingSubmission($details));
            $dealer = Dealer::findOrFail($event->dealerTemp->dealer_id);
            // dd($dealer->name);
            $notification = $member->notifications->first();
            $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
            $notification->notification_marketing_group_id = "5";
            $notification->notified_feature = "dealer";
            $notification->notification_text = "Perubahan data " . $dealer->name . " membutuhkan revisi";
            $notification->mobile_link = "";
            $notification->desktop_link = "/marketing-staff/dealer-revised-staff/" . $event->dealerTemp->id;
            $notification->as_marketing = true;
            $notification->personel_id = $event->dealerTemp->submited_by;
            $notification->status = "revised change";
            $notification->data_id = $event->dealerTemp->id;

            $notification->save();
            
            $marketing_supervisor = $this->parentPersonel($event->dealerTemp->personel_id);
            foreach ($marketing_supervisor as $key => $value) {
                $member = User::withTrashed()->where("personel_id", $value)->first();
                if ($member) {
                    $member->notify(new DealerMarketingSubmission($details));
    
                    $notification = $member->notifications->first();
                    $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
                    $notification->notification_marketing_group_id = "5";
                    $notification->notified_feature = "dealer";
                    $notification->notification_text = "Perubahan data " . $dealer->name . " membutuhkan revisi";
                    $notification->mobile_link = "";
                    $notification->desktop_link = "/marketing-staff/dealer-revised-staff/" . $event->dealerTemp->id;
                    $notification->as_marketing = false;
                    $notification->status = "revised change";
                    $notification->personel_id = $event->dealerTemp->personel_id;
                    $notification->data_id = $event->dealerTemp->id;
    
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
                ->whereHas('user.personel', function ($q) use ($member) {
                    return $q->where('id', $member->personel_id);
                })
                ->where('is_active', true)
                ->get()
                ->each(function ($player) use ($event) {
                    $oneData = [
                        "player_id" => $player->os_player_id,
                        "subtitle" => "Perubahan data " . $event->dealerTemp->name . " membutuhkan revisi",
                        "data_id" => $event->dealerTemp->id,
                        "message" => "Perubahan data " . $event->dealerTemp->name . " membutuhkan revisi",
                        "mobile_link" => "",
                        "notification" => "Perubahan data " . $event->dealerTemp->name . " membutuhkan revisi",
                        "contents" => [
                            "en" => "Perubahan data " . $event->dealerTemp->name . " membutuhkan revisi",
                            "in" => "Perubahan data " . $event->dealerTemp->name . " membutuhkan revisi",
                        ],
                        "menu" => "Dealer",
                        "is_supervisor" => false,
                    ];
    
                    // dump($oneData);
    
                    return (new OneSignalPushNotificationAction)->execute($oneData);
                });
        }

    }
}
