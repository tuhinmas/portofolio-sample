<?php

namespace Modules\KiosDealer\Notifications;

use App\Traits\Uuids;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DealerMarketingSubmission extends Notification
{
    use Queueable;
    use Uuids;

    protected $details;
    protected $notification;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($details)
    {
        $this->detail = $details;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toDatabase($notifiable)
    {
        $notifiable = $notifiable;
        unset($notifiable->notifications);
        return [
            'detail' => $this->detail,
            'expired_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta'),
            'created_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->setTimezone('Asia/Jakarta'),
            'user' => $notifiable,
        ];
    }

    /**
     * Get the array representation of the notification.,
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }

}
