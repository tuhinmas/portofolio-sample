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
use Modules\KiosDealer\Notifications\StoreTempNotification;
use Modules\Personel\Entities\Personel;

class StoreTempNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 2000;

    public $tries = 2;

    protected $storeTemp;

    public function __construct($storeTemp)
    {
        $this->storeTemp = $storeTemp;
    }

    public function handle()
    {
        switch ($this->storeTemp->status) {
            case 'filed' || 'submission of changes':
                $this->filed($this->storeTemp);
                break;

            case 'accepted':
                $this->accepted($this->storeTemp);
                break;

            case 'revised':
                $this->revised($this->storeTemp);
                break;

            case 'change rejected':
                $this->changeRejected($this->storeTemp);
                break;

            case 'filed rejected':
                $this->changeRejected($this->storeTemp);
                break;
            
            default:
                break;
        }
    }

    private function filed($storeTemp)
    {
        $users = User::with(['userDevices', 'permissions'])
            ->withTrashed()
            ->whereHas('permissions', function ($q) {
                return $q->whereIn('name', [
                    '(S) Konfirmasi Kios',
                ]);
            })
            ->get();
        
        $userDevices = $users->map(function ($q) {
            return $q->userDevices->map(function ($q) {
                return $q->os_player_id;
            })->toArray();
        })->flatten()->toArray();

        $personelName = $storeTemp->personel?->name ?? null;

        $textNotif = "pengajuan kios dan perubahan data kios perlu ditinjau";
        $fields = [
            "include_player_ids" => $userDevices,
            "data" => [
                "subtitle" => "Kios",
                "menu" => "Kiost",
                "data_id" => $storeTemp->id,
                "mobile_link" => "",
                "desktop_link" => "/marketing-support/store/store-confirmation",
                "notification" => $textNotif,
                "is_supervisor" => false,
            ],
            "contents" => [
                "en" => $textNotif,
                "in" => $textNotif,
            ],
            "recipients" => 1,
        ];

        $this->notif($users, $storeTemp, $textNotif, $fields["data"]["mobile_link"], $fields["data"]["desktop_link"]);

        return OneSignal::sendPush($fields, $textNotif);
    }

    private function accepted($storeTemp)
    {
        $users = User::with(['userDevices', 'permissions'])
            ->withTrashed()
            ->where("personel_id", $storeTemp->personel_id)
            ->get();
        
        $userDevices = $users->map(function ($q) {
            return $q->userDevices->map(function ($q) {
                return $q->os_player_id;
            })->toArray();
        })->flatten()->toArray();
        
        $personelName = $storeTemp->personel?->name ?? null;

        $textNotif = "Pengajuan kios $storeTemp->name telah disetujui oleh support";
        $fields = [
            "include_player_ids" => $userDevices,
            "data" => [
                "subtitle" => "Kios",
                "menu" => "Kios",
                "data_id" => $storeTemp->id,
                "mobile_link" => "",
                "desktop_link" => "/marketing-staff/kios-detail-data/".$storeTemp->id,
                "notification" => $textNotif,
                "is_supervisor" => false,
            ],
            "contents" => [
                "en" => $textNotif,
                "in" => $textNotif,
            ],
            "recipients" => 1,
        ];

        $this->notif($users, $storeTemp, $textNotif, $fields["data"]["mobile_link"], $fields["data"]["desktop_link"]);


        return OneSignal::sendPush($fields, $textNotif);
    }

    private function revised($storeTemp)
    {
        $users = User::with(['userDevices', 'permissions'])
            ->withTrashed()
            ->where("personel_id", $storeTemp->personel_id)
            ->get();
        
        $userDevices = $users->map(function ($q) {
            return $q->userDevices->map(function ($q) {
                return $q->os_player_id;
            })->toArray();
        })->flatten()->toArray();
        
        $personelName = $storeTemp->personel?->name ?? null;

        $textNotif = "pengajuan data $storeTemp->name Membutuhkan Revisi";
        $fields = [
            "include_player_ids" => $userDevices,
            "data" => [
                "subtitle" => "Pengajuan Sub Dealer Baru",
                "menu" => "Sub Dealer Temp",
                "data_id" => $storeTemp->id,
                "mobile_link" => "",
                "desktop_link" => "/marketing-staff/sub-dealer-detail/".$storeTemp->id,
                "notification" => $textNotif,
                "is_supervisor" => false,
            ],
            "contents" => [
                "en" => $textNotif,
                "in" => $textNotif,
            ],
            "recipients" => 1,
        ];

        $this->notif($users, $storeTemp, $textNotif, $fields["data"]["mobile_link"], $fields["data"]["desktop_link"]);


        return OneSignal::sendPush($fields, $textNotif);
    }

    private function changeRejected($storeTemp)
    {
        $users = User::with(['userDevices', 'permissions'])
            ->withTrashed()
            ->where("personel_id", $storeTemp->personel_id)
            ->get();
        
        $userDevices = $users->map(function ($q) {
            return $q->userDevices->map(function ($q) {
                return $q->os_player_id;
            })->toArray();
        })->flatten()->toArray();
        
        $personelName = $storeTemp->personel?->name ?? null;

        $textNotif = "Pengajuan Perubahan Data kios $storeTemp->name telah ditolak oleh support";
        $fields = [
            "include_player_ids" => $userDevices,
            "data" => [
                "subtitle" => "Kios",
                "menu" => "Kios",
                "data_id" => $storeTemp->id,
                "mobile_link" => "",
                "desktop_link" => "/marketing-staff/kios",
                "notification" => $textNotif,
                "is_supervisor" => false,
            ],
            "contents" => [
                "en" => $textNotif,
                "in" => $textNotif,
            ],
            "recipients" => 1,
        ];

        $this->notif($users, $storeTemp, $textNotif, $fields["data"]["mobile_link"], $fields["data"]["desktop_link"]);


        return OneSignal::sendPush($fields, $textNotif);
    }

    private function notif($users, $storeTemp, $notificationText, $mobileLink, $desktopLink)
    {
        foreach (($users ?? []) as $user) {
            $member =  User::withTrashed()->where("id", $user->id)->first();
            $detail = [
                'notification_marketing_group_id' => 8,
                'personel_id' => $storeTemp->personel_id,
                'notified_feature' => "sub_dealer",
                'notification_text' => $notificationText,
                'mobile_link' => $mobileLink,
                'desktop_link' => $desktopLink,
                'data_id' => $storeTemp->id,
                'expired_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta'),
                'as_marketing' => true,
                'status' => $storeTemp->status,
                'notifiable_id' => $storeTemp->personel_id,
            ];

            $member->notify(new StoreTempNotification($detail));
            $notification = $member->notifications->first();
            $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
            $notification->notification_marketing_group_id = "8";
            $notification->notified_feature = "sub_dealer";
            $notification->notifiable_id = $user->id;
            $notification->personel_id = $storeTemp->personel_id;
            $notification->notification_text = $notificationText;
            $notification->mobile_link = $mobileLink;
            $notification->desktop_link = $desktopLink;
            $notification->as_marketing = true;
            $notification->status = $storeTemp->status;
            $notification->data_id = $storeTemp->id;
            $notification->save();
        }
    }
}

