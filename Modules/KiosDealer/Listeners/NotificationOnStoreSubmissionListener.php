<?php

namespace Modules\KiosDealer\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\KiosDealer\Events\StoreOnUpdateEvent;
use Modules\KiosDealer\Notifications\KiosSubmission;

class NotificationOnStoreSubmissionListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(StoreOnUpdateEvent $event)
    {
        $user = $event->store_temp->personel->user;
        
        if ($user) {
            $details = [
                'notified_feature' => "Kios",
                'notification_text' => "pengajuan kios dan perubahan data kios perlu ditinjau",
                'mobile_link' => "/ListSubmissionStore",
                'desktop_link' => "/marketing-support/store/store-confirmation",
                'data_id' => $event->store_temp->id,
                'as_marketing' => true,
            ];

            $external_data = [
                "notification_marketing_group_id" => "8",
                "notified_feature" => "Kios",
                "notification_text" => "pengajuan kios dan perubahan data kios perlu ditinjau",
                "mobile_link" => "/ListSubmissionStore",
                "desktop_link" => "/marketing-support/store/store-confirmation",
                "as_marketing" => true,
                "status" => "filed,submission of changes",
                "data_id" => $event->store_temp->id,
                "personel_id" => $event->store_temp->personel_id,
            ];

            $notif = $user->notify(new KiosSubmission($details, $external_data));
        }
        
    }
}
