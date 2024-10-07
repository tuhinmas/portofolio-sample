<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class GroupedDbChannel
{
    public function send($notifiable, Notification $notification)
    {
        $data = $notification->toDatabase($notifiable);
        return $notifiable->routeNotificationFor('database')->create([
            'id' => $notification->id,
            'type' => get_class($notification),
            'data' => $data,
            'read_at' => null,
            'expired_at' =>  $data["expired_at"],
            "personel_id" => $data["personel_id"],
            "notification_marketing_group_id" => $data["notification_marketing_group_id"],
            "notified_feature" => $data["notified_feature"],
            "notification_text" => $data["notification_text"],
            "mobile_link" => $data["mobile_link"],
            "desktop_link" => $data["desktop_link"],
            "as_marketing" => $data["as_marketing"],
            "status" => $data["status"],
            "data_id" => $data["data_id"],
        ]);
    }
}
