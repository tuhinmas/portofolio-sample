<?php

namespace Modules\KiosDealer\Notifications;

use App\Traits\Uuids;
use Illuminate\Bus\Queueable;
use App\Notifications\GroupedDbChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class KiosSubmission extends Notification
{
    use Queueable;
    use Uuids;

    protected $external_data;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($detail, $external_data)
    {
        $this->detail = $detail;
        $this->external_data = $external_data;
    }


    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [GroupedDbChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toDatabase($notifiable)
    {
        return [
            'detail' => $this->detail,
            'expired_at' => now()->addDays(30),
            'created_at' => now(),
            'user' => $notifiable,
            "notification_marketing_group_id" => $this->external_data["notification_marketing_group_id"],
            "notified_feature" => $this->external_data["notified_feature"],
            "notification_text" => $this->external_data["notification_text"],
            "mobile_link" => $this->external_data["mobile_link"],
            "desktop_link" => $this->external_data["desktop_link"],
            "as_marketing" => $this->external_data["as_marketing"],
            "status" => $this->external_data["status"],
            "data_id" => $this->external_data["data_id"],
            'personel_id' => $this->external_data["personel_id"],
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
