<?php

namespace Modules\KiosDealer\Notifications;

use Illuminate\Notifications\Notification;

class CustomDbChannel 
{

  public function send($notifiable, Notification $notification)
  {
    $data = $notification->toDatabase($notifiable);    
    return $notifiable->routeNotificationFor('database')->create([
        'id' => $notification->id,

        // 'answer_id' => $data['answer_id'], //<-- comes from toDatabase() Method below
        // 'detail' => $this->detail,
        // 'expired_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta'),
        // 'created_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->setTimezone('Asia/Jakarta'),
        // 'user' => $notifiable,
        // "notification_marketing_group_id" => $this->notification["notification_marketing_group_id"],
        // "notified_feature" => $this->notification["notified_feature"],
        // "notification_text" => $this->notification["notification_text"],
        // "mobile_link" => $this->notification["mobile_link"],
        // "desktop_link" => $this->notification["desktop_link"],
        // "as_marketing" => $this->notification["as_marketing"],
        // "status" => $this->notification["status"],
        "data_id" => $data["data_id"],
        // 'user_id'=> \Auth::user()->id,

        // 'type' => get_class($notification),
        // 'data' => $data,
        // 'read_at' => null,
    ]);
  }

}