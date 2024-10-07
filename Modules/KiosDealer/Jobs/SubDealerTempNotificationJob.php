<?php

namespace Modules\KiosDealer\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Ladumor\OneSignal\OneSignal;
use Modules\Authentication\Entities\User;
use Modules\KiosDealer\Notifications\SubDealerTempNotification;
use Modules\Personel\Entities\Personel;

class SubDealerTempNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 2000;

    public $tries = 2;

    protected $subDealerTemp;

    public function __construct($subDealerTemp)
    {
        $this->subDealerTemp = $subDealerTemp;
    }

    public function handle()
    {
        switch ($this->subDealerTemp->status) {
            case 'filed':
                $this->filed($this->subDealerTemp);
                break;

            case 'submission of changes':
                $this->submissionOfChange($this->subDealerTemp);
                break;

            case 'accepted':
                $this->accepted($this->subDealerTemp);
                break;

            case 'revised':
                $this->revised($this->subDealerTemp);
                break;

            case 'filed rejected':
                $this->filedRejected($this->subDealerTemp);
                break;

            case 'change rejected':
                $this->changeRejected($this->subDealerTemp);
                break;

            case 'revised change':
                $this->revisedChange($this->subDealerTemp);
                break;

            default:
                # code...
                break;
        }
    }

    private function filed($subDealerTemp)
    {
        $users = User::with(['userDevices', 'permissions'])
            ->withTrashed()
            ->whereHas('permissions', function ($q) {
                return $q->whereIn('name', [
                    '(S) Konfirmasi Sub-Dealer',
                ]);
            })
            ->get();

        $userDevices = $users->map(function ($q) {
            return $q->userDevices->map(function ($q) {
                return $q->os_player_id;
            })->toArray();
        })->flatten()->toArray();

        $personelName = $subDealerTemp->personel?->name ?? null;

        $textNotif = "Pengajuan subdealer baru dari $personelName perlu ditinjau";
        $fields = [
            "include_player_ids" => $userDevices,
            "data" => [
                "subtitle" => "Pengajuan Sub Dealer Baru",
                "menu" => "Sub Dealer Temp",
                "data_id" => $subDealerTemp->id,
                "mobile_link" => "",
                "desktop_link" => "/marketing-support/sub-dealer-confirmation/" . $subDealerTemp->id,
                "notification" => $textNotif,
                "is_supervisor" => false,
            ],
            "contents" => [
                "en" => $textNotif,
                "in" => $textNotif,
            ],
            "recipients" => 1,
        ];

        $this->notif($users, $subDealerTemp, $textNotif, $fields["data"]["mobile_link"], $fields["data"]["desktop_link"]);

        return OneSignal::sendPush($fields, $textNotif);
    }

    private function submissionOfChange($subDealerTemp)
    {
        $users = User::with(['userDevices', 'permissions'])
            ->withTrashed()
            ->whereHas('permissions', function ($q) {
                return $q->whereIn('name', [
                    '(S) Konfirmasi Sub-Dealer',
                ]);
            })
            ->get();

        $userDevices = $users->map(function ($q) {
            return $q->userDevices->map(function ($q) {
                return $q->os_player_id;
            })->toArray();
        })->flatten()->toArray();

        $personelName = $subDealerTemp->personel?->name ?? null;

        $textNotif = "Pengajuan perubahan subdealer $subDealerTemp->name dari $personelName perlu ditinjau";
        $fields = [
            "include_player_ids" => $userDevices,
            "data" => [
                "subtitle" => "Pengajuan Sub Dealer Baru",
                "menu" => "Sub Dealer Temp",
                "data_id" => $subDealerTemp->id,
                "mobile_link" => "",
                "desktop_link" => "/marketing-support/sub-dealer-confirmation/" . $subDealerTemp->id,
                "notification" => $textNotif,
                "is_supervisor" => false,
            ],
            "contents" => [
                "en" => $textNotif,
                "in" => $textNotif,
            ],
            "recipients" => 1,
        ];

        $this->notif($users, $subDealerTemp, $textNotif, $fields["data"]["mobile_link"], $fields["data"]["desktop_link"]);

        return OneSignal::sendPush($fields, $textNotif);
    }

    private function accepted($subDealerTemp)
    {
        $users = User::with(['userDevices', 'permissions'])
            ->withTrashed()
            ->where("personel_id", $subDealerTemp->personel_id)
            ->get();

        $userDevices = $users->map(function ($q) {
            return $q->userDevices->map(function ($q) {
                return $q->os_player_id;
            })->toArray();
        })->flatten()->toArray();

        $personelName = $subDealerTemp->personel?->name ?? null;

        $textNotif = "pengajuan data $subDealerTemp->name telah dikonfirmasi";
        $fields = [
            "include_player_ids" => $userDevices,
            "data" => [
                "subtitle" => "Pengajuan Sub Dealer Baru",
                "menu" => "Sub Dealer Temp",
                "data_id" => $subDealerTemp->id,
                "mobile_link" => "",
                "desktop_link" => "/marketing-staff/sub-dealer-detail/" . $subDealerTemp->id,
                "notification" => $textNotif,
                "is_supervisor" => false,
            ],
            "contents" => [
                "en" => $textNotif,
                "in" => $textNotif,
            ],
            "recipients" => 1,
        ];

        $this->notif($users, $subDealerTemp, $textNotif, $fields["data"]["mobile_link"], $fields["data"]["desktop_link"]);

        return OneSignal::sendPush($fields, $textNotif);
    }

    private function revised($subDealerTemp)
    {
        $users = User::with(['userDevices', 'permissions'])
            ->withTrashed()
            ->where("personel_id", $subDealerTemp->personel_id)
            ->get();

        $userDevices = $users->map(function ($q) {
            return $q->userDevices->map(function ($q) {
                return $q->os_player_id;
            })->toArray();
        })->flatten()->toArray();

        $personelName = $subDealerTemp->personel?->name ?? null;

        $textNotif = "pengajuan data $subDealerTemp->name Membutuhkan Revisi";
        $fields = [
            "include_player_ids" => $userDevices,
            "data" => [
                "subtitle" => "Pengajuan Sub Dealer Baru",
                "menu" => "Sub Dealer Temp",
                "data_id" => $subDealerTemp->id,
                "mobile_link" => "",
                "desktop_link" => "/marketing-staff/sub-dealer-detail/" . $subDealerTemp->id,
                "notification" => $textNotif,
                "is_supervisor" => false,
            ],
            "contents" => [
                "en" => $textNotif,
                "in" => $textNotif,
            ],
            "recipients" => 1,
        ];

        $this->notif($users, $subDealerTemp, $textNotif, $fields["data"]["mobile_link"], $fields["data"]["desktop_link"]);

        return OneSignal::sendPush($fields, $textNotif);
    }

    private function filedRejected($subDealerTemp)
    {
        $users = User::with(['userDevices', 'permissions'])
            ->withTrashed()
            ->where("personel_id", $subDealerTemp->personel_id)
            ->get();

        $userDevices = $users->map(function ($q) {
            return $q->userDevices->map(function ($q) {
                return $q->os_player_id;
            })->toArray();
        })->flatten()->toArray();

        $personelName = $subDealerTemp->personel?->name ?? null;

        $textNotif = "pengajuan data $subDealerTemp->name Ditolak";
        $fields = [
            "include_player_ids" => $userDevices,
            "data" => [
                "subtitle" => "Pengajuan Data",
                "menu" => "Sub Dealer Temp",
                "data_id" => $subDealerTemp->id,
                "mobile_link" => "",
                "desktop_link" => "/marketing-staff/sub-dealer",
                "notification" => $textNotif,
                "is_supervisor" => false,
            ],
            "contents" => [
                "en" => $textNotif,
                "in" => $textNotif,
            ],
            "recipients" => 1,
        ];

        $this->notif($users, $subDealerTemp, $textNotif, $fields["data"]["mobile_link"], $fields["data"]["desktop_link"]);

        return OneSignal::sendPush($fields, $textNotif);
    }

    private function changeRejected($subDealerTemp)
    {
        $users = User::with(['userDevices', 'permissions'])
            ->withTrashed()
            ->where("personel_id", $subDealerTemp->personel_id)
            ->get();

        $userDevices = $users->map(function ($q) {
            return $q->userDevices->map(function ($q) {
                return $q->os_player_id;
            })->toArray();
        })->flatten()->toArray();

        $personelName = $subDealerTemp->personel?->name ?? null;

        $textNotif = "pengajuan Perubaha data $subDealerTemp->name Ditolak";
        $fields = [
            "include_player_ids" => $userDevices,
            "data" => [
                "subtitle" => "Pengajuan Perubahan Sub Dealer",
                "menu" => "Sub Dealer Temp",
                "data_id" => $subDealerTemp->id,
                "mobile_link" => "",
                "desktop_link" => "/marketing-staff/sub-dealer",
                "notification" => $textNotif,
                "is_supervisor" => false,
            ],
            "contents" => [
                "en" => $textNotif,
                "in" => $textNotif,
            ],
            "recipients" => 1,
        ];

        $this->notif($users, $subDealerTemp, $textNotif, $fields["data"]["mobile_link"], $fields["data"]["desktop_link"]);

        return OneSignal::sendPush($fields, $textNotif);
    }

    private function revisedChange($subDealerTemp)
    {
        $users = User::query()
            ->with(['userDevices'])
            ->withTrashed()
            ->where("personel_id", $subDealerTemp->personel_id)
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        $userDevices = $users
            ->map(function ($q) {
                return $q
                    ->userDevices
                    ->map(function ($q) {
                        return $q->os_player_id;
                    })
                    ->toArray();
            })
            ->flatten()
            ->toArray();

        $personelName = $subDealerTemp->personel?->name ?? null;

        $textNotif = "Perubaha data $subDealerTemp->name Membutuhkan Revisi";
        $fields = [
            "include_player_ids" => $userDevices,
            "data" => [
                "subtitle" => "Perubahan SubDealer",
                "menu" => "Sub Dealer Temp",
                "data_id" => $subDealerTemp->id,
                "mobile_link" => "/ListSubDealerTemp",
                "desktop_link" => "/marketing-staff/subdealer-revisedchange-staff/" . $subDealerTemp->id . '/edit',
                "notification" => $textNotif,
                "is_supervisor" => false,
            ],
            "contents" => [
                "en" => $textNotif,
                "in" => $textNotif,
            ],
            "recipients" => 1,
        ];

        $this->notif($users, $subDealerTemp, $textNotif, $fields["data"]["mobile_link"], $fields["data"]["desktop_link"]);
        $notif = OneSignal::sendPush($fields, $textNotif);

        return $notif;
    }

    private function notif($users, $subDealerTemp, $notificationText, $mobileLink, $desktopLink)
    {
        foreach (($users ?? []) as $user) {
            $details = [
                'notified_feature' => "sub_dealer",
                'notification_text' => $notificationText,
                'mobile_link' => $mobileLink,
                'desktop_link' => $desktopLink,
                "data_id" => $subDealerTemp->id,
                'as_marketing' => true,
            ];

            $external_data = [
                "notification_marketing_group_id" => 6,
                "notified_feature" => "sub_dealer",
                "notification_text" => $notificationText,
                "mobile_link" => $mobileLink,
                "desktop_link" => $desktopLink,
                "as_marketing" => true,
                "status" => $subDealerTemp->status,
                "data_id" => $subDealerTemp->id,
                "personel_id" => $subDealerTemp->personel_id,
            ];

            if ($user) {
                $notif = $user->notify(new SubDealerTempNotification($details, $external_data));
            }
        }
    }
}
