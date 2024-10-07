<?php

namespace App\Actions\Notifications;

use Ladumor\OneSignal\OneSignal;

class OneSignalPushNotificationAction
{
    public function execute($payloads)
    {
        extract($payloads);

        $fields = [
            "include_player_ids" => [$player_id],
            "data" => [
                "subtitle" => $subtitle,
                "menu" => $menu,
                "data_id" => $data_id,
                "mobile_link" => $mobile_link,
                "notification" => $notification,
                "is_supervisor" => $is_supervisor
            ],
            "contents" => $contents,
            "recipients" => 1,
        ];

        $notif = OneSignal::sendPush($fields, $message);
        return $notif;
    }
}
