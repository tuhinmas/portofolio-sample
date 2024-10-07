<?php

namespace Modules\KiosDealer\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Ladumor\OneSignal\OneSignal;
use Modules\Authentication\Entities\User;
use Modules\KiosDealer\Notifications\DealerNotification;
use Modules\KiosDealer\Notifications\DealerTempNotification;
use Modules\KiosDealer\Notifications\StoreTempNotification;
use Modules\Personel\Entities\Personel;

class DealerNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 2000;

    public $tries = 2;

    protected $dealer, $statusBefore;

    public function __construct($dealer, $statusBefore)
    {
        $this->dealer = $dealer;
        $this->statusBefore = $statusBefore;
    }

    public function handle()
    {
        if ($this->dealer->status == 'accepted') {
            if ($this->statusBefore == "submission of changes") {
                $params = [
                    "text_notif" => "Perubahan dealer ".$this->dealer->name." Disetujui."
                ];
            }else{
                $params = [
                    "text_notif" => "Pengajuan dealer ".$this->dealer->name." Disetujui."
                ];
            }
            
            $this->accepted($params);
            return;
        }elseif ($this->dealer->status == 'rejected') {
            $this->rejected();
            return;
        }
    }

    private function accepted($params = [])
    {
        $data = $this->dealer;

        $users = User::with(['userDevices', 'permissions'])
            ->where("personel_id", $data->personel_id)
            ->get();
        
        $userDevices = $users->map(function ($q) {
            return $q->userDevices->map(function ($q) {
                return $q->os_player_id;
            })->toArray();
        })->flatten()->toArray();

        $textNotif = $params['text_notif'];
        $fields = [
            "include_player_ids" => $userDevices,
            "data" => [
                "subtitle" => $textNotif,
                "menu" => "detail dealer",
                "data_id" => $data->id,
                "mobile_link" => "/DealerInfo",
                "desktop_link" => "/marketing-staff/detail-dealer/",
                "notification" => $textNotif,
                "is_supervisor" => false,
            ],
            "contents" => [
                "en" => $textNotif,
                "in" => $textNotif,
            ],
            "recipients" => 1,
        ];

        foreach (($users ?? []) as $user) {
            $member =  User::withTrashed()->where("id", $user->id)->first();
            $detail = [
                'notification_marketing_group_id' => 5,
                'personel_id' => $data->personel_id,
                'notified_feature' => "dealer",
                'notification_text' => $textNotif,
                'mobile_link' => $fields['data']['mobile_link'],
                'desktop_link' => $fields['data']['desktop_link'],
                'data_id' => $data->id,
                'expired_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta'),
                'as_marketing' => true,
                'status' => $data->status,
                'notifiable_id' => $data->personel_id,
            ];

            $member->notify(new DealerNotification($detail));
            $notification = $member->notifications->first();
            $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
            $notification->notification_marketing_group_id = 5;
            $notification->notified_feature = "dealer";
            $notification->notifiable_id = $data->id;
            $notification->personel_id = $data->personel_id;
            $notification->notification_text = $textNotif;
            $notification->mobile_link = $fields['data']['mobile_link'];
            $notification->desktop_link = $fields['data']['desktop_link'];
            $notification->as_marketing = true;
            $notification->status = $data->status;
            $notification->data_id = $data->id;
            $notification->save();
        }

        return OneSignal::sendPush($fields, $textNotif);
    }

    private function rejected()
    {
        $data = $this->dealer;

        $users = User::with(['userDevices', 'permissions'])
            ->where("personel_id", $data->personel_id)
            ->get();
        
        $userDevices = $users->map(function ($q) {
            return $q->userDevices->map(function ($q) {
                return $q->os_player_id;
            })->toArray();
        })->flatten()->toArray();

        $textNotif = "pengajuan data ".$data->name." ditolak";
        $fields = [
            "include_player_ids" => $userDevices,
            "data" => [
                "subtitle" => $textNotif,
                "menu" => "detail dealer",
                "data_id" => $data->id,
                "mobile_link" => "/DealerInfo",
                "desktop_link" => "/marketing-staff/detail-dealer/",
                "notification" => $textNotif,
                "is_supervisor" => false,
            ],
            "contents" => [
                "en" => $textNotif,
                "in" => $textNotif,
            ],
            "recipients" => 1,
        ];

        foreach (($users ?? []) as $user) {
            $member =  User::withTrashed()->where("id", $user->id)->first();
            $detail = [
                'notification_marketing_group_id' => 5,
                'personel_id' => $data->personel_id,
                'notified_feature' => "dealer",
                'notification_text' => $textNotif,
                'mobile_link' => $fields['data']['mobile_link'],
                'desktop_link' => $fields['data']['desktop_link'],
                'data_id' => $data->id,
                'expired_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta'),
                'as_marketing' => true,
                'status' => $data->status,
                'notifiable_id' => $data->personel_id,
            ];

            $member->notify(new DealerNotification($detail));
            $notification = $member->notifications->first();
            $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
            $notification->notification_marketing_group_id = 5;
            $notification->notified_feature = "dealer";
            $notification->notifiable_id = $data->id;
            $notification->personel_id = $data->personel_id;
            $notification->notification_text = $textNotif;
            $notification->mobile_link = $fields['data']['mobile_link'];
            $notification->desktop_link = $fields['data']['desktop_link'];
            $notification->as_marketing = true;
            $notification->status = $data->status;
            $notification->data_id = $data->id;
            $notification->save();
        }

        return OneSignal::sendPush($fields, $textNotif);
    }

}

