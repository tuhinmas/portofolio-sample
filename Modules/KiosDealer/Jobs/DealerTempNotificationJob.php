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
use Modules\KiosDealer\Notifications\DealerTempNotification;
use Modules\KiosDealer\Notifications\StoreTempNotification;
use Modules\Personel\Entities\Personel;

class DealerTempNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 2000;

    public $tries = 2;

    protected $dealerTemp, $statusBefore;

    public function __construct($dealerTemp, $statusBefore)
    {
        $this->dealerTemp = $dealerTemp;
        $this->statusBefore = $statusBefore;
    }

    public function handle()
    {
        if ($this->statusBefore == "filed" && $this->dealerTemp->status == 'wait approval') {
            $this->confirmed();
            $this->approvalSupport();
            return;
        }elseif ($this->dealerTemp->status == "filed rejected") {
            return $this->filedRejected();
        }elseif ($this->dealerTemp->status == "revised") {
            return $this->filedRejected();
        }elseif ($this->statusBefore == "submission of change" && $this->dealerTemp->status == 'wait approval') {
            $this->changeConfirmed();
            $this->changeConfirmedSupport();
            return;
        }elseif ($this->dealerTemp->status == "change rejected") {
            return $this->changeRejected();
        }elseif ($this->dealerTemp->status == "revised change") {
            return $this->revisedChange();
        }elseif ($this->dealerTemp->status == "filed") {
            return $this->filedSupport();
        }elseif ($this->dealerTemp->status == "submission of changes") {
            return $this->submissionOfChangeSupport();
        }
        
    }

    private function confirmed()
    {
        $data = $this->dealerTemp;

        $users = User::with(['userDevices', 'permissions'])
            ->where("personel_id", $data->submited_by)
            ->get();
        
        $userDevices = $users->map(function ($q) {
            return $q->userDevices->map(function ($q) {
                return $q->os_player_id;
            })->toArray();
        })->flatten()->toArray();

        $textNotif = "pengajuan data ".$data->name." telah dikonfirmasi";
        $fields = [
            "include_player_ids" => $userDevices,
            "data" => [
                "subtitle" => $textNotif,
                "menu" => "detail dealer",
                "data_id" => $data->id,
                "mobile_link" => "/DetailDealerTemp",
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

            $member->notify(new DealerTempNotification($detail));
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

    private function filedRejected()
    {
        $data = $this->dealerTemp;

        $users = User::with(['userDevices', 'permissions'])
            ->where("personel_id", $data->submited_by)
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
                "mobile_link" => "/DetailDealerTemp",
                "desktop_link" => "/marketing-staff/data-dealer",
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

            $member->notify(new DealerTempNotification($detail));
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

    private function revised()
    {
        $data = $this->dealerTemp;

        $users = User::with(['userDevices', 'permissions'])
            ->where("personel_id", $data->submited_by)
            ->get();
        
        $userDevices = $users->map(function ($q) {
            return $q->userDevices->map(function ($q) {
                return $q->os_player_id;
            })->toArray();
        })->flatten()->toArray();

        $textNotif = "pengajuan data ".$data->name." membutuhkan revisi";
        $fields = [
            "include_player_ids" => $userDevices,
            "data" => [
                "subtitle" => $textNotif,
                "menu" => "detail dealer",
                "data_id" => $data->id,
                "mobile_link" => "",
                "desktop_link" => "/marketing-staff/dealer-revised-staff/".$data->id,
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

            $member->notify(new DealerTempNotification($detail));
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

    private function changeConfirmed()
    {
        $data = $this->dealerTemp;

        $users = User::with(['userDevices', 'permissions'])
            ->where("personel_id", $data->submited_by)
            ->get();
        
        $userDevices = $users->map(function ($q) {
            return $q->userDevices->map(function ($q) {
                return $q->os_player_id;
            })->toArray();
        })->flatten()->toArray();

        $textNotif = "Perubahan data ".$data->name." dikonfirmasi";
        $fields = [
            "include_player_ids" => $userDevices,
            "data" => [
                "subtitle" => $textNotif,
                "menu" => "detail dealer",
                "data_id" => $data->id,
                "mobile_link" => "/DetailDealerTemp",
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

            $member->notify(new DealerTempNotification($detail));
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

    private function changeRejected()
    {
        $data = $this->dealerTemp;

        $users = User::with(['userDevices', 'permissions'])
            ->where("personel_id", $data->submited_by)
            ->get();
        
        $userDevices = $users->map(function ($q) {
            return $q->userDevices->map(function ($q) {
                return $q->os_player_id;
            })->toArray();
        })->flatten()->toArray();

        $textNotif = "Perubahan data ".$data->name." ditolak";
        $fields = [
            "include_player_ids" => $userDevices,
            "data" => [
                "subtitle" => $textNotif,
                "menu" => "detail dealer",
                "data_id" => $data->id,
                "mobile_link" => "/DetailDealerTemp",
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

            $member->notify(new DealerTempNotification($detail));
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

    private function revisedChange()
    {
        $data = $this->dealerTemp;

        $users = User::with(['userDevices', 'permissions'])
            ->where("personel_id", $data->submited_by)
            ->get();
        
        $userDevices = $users->map(function ($q) {
            return $q->userDevices->map(function ($q) {
                return $q->os_player_id;
            })->toArray();
        })->flatten()->toArray();

        $textNotif = "Perubahan data ".$data->name." membutuhkan revisi";
        $fields = [
            "include_player_ids" => $userDevices,
            "data" => [
                "subtitle" => $textNotif,
                "menu" => "detail dealer",
                "data_id" => $data->id,
                "mobile_link" => "/DetailDealerTemp",
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

            $member->notify(new DealerTempNotification($detail));
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

    private function filedSupport()
    {
        $data = $this->dealerTemp;

        $users = User::with(['userDevices', 'permissions'])
            ->whereHas('permissions', function ($q) {
                return $q->whereIn('name', [
                    '(S) Konfirmasi Dealer',
                ]);
            })
            ->get();
        
        $userDevices = $users->map(function ($q) {
            return $q->userDevices->map(function ($q) {
                return $q->os_player_id;
            })->toArray();
        })->flatten()->toArray();

        $textNotif = "Pengajuan dealer baru dari ".optional($data->personel)->name." perlu ditinjau";
        $fields = [
            "include_player_ids" => $userDevices,
            "data" => [
                "subtitle" => $textNotif,
                "menu" => "detail dealer",
                "data_id" => $data->id,
                "mobile_link" => "/DetailDealerTemp",
                "desktop_link" => "/marketing-support/dealer/detail-confirm-dealer/".$data->id,
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
                'as_marketing' => false,
                'status' => $data->status,
                'notifiable_id' => $data->personel_id,
            ];

            $member->notify(new DealerTempNotification($detail));
            $notification = $member->notifications->first();
            $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
            $notification->notification_marketing_group_id = 5;
            $notification->notified_feature = "dealer";
            $notification->notifiable_id = $data->id;
            $notification->personel_id = $data->personel_id;
            $notification->notification_text = $textNotif;
            $notification->mobile_link = $fields['data']['mobile_link'];
            $notification->desktop_link = $fields['data']['desktop_link'];
            $notification->as_marketing = false;
            $notification->status = $data->status;
            $notification->data_id = $data->id;
            $notification->save();
        }

        return OneSignal::sendPush($fields, $textNotif);
    }

    private function submissionOfChangeSupport()
    {
        $data = $this->dealerTemp;

        $users = User::with(['userDevices', 'permissions'])
            ->whereHas('permissions', function ($q) {
                return $q->whereIn('name', [
                    '(S) Konfirmasi Dealer',
                ]);
            })
            ->get();
        
        $userDevices = $users->map(function ($q) {
            return $q->userDevices->map(function ($q) {
                return $q->os_player_id;
            })->toArray();
        })->flatten()->toArray();

        $textNotif = "Pengajuan perubahan dealer".$data->name." dari ".optional($data->personel)->name." perlu ditinjau";
        $fields = [
            "include_player_ids" => $userDevices,
            "data" => [
                "subtitle" => $textNotif,
                "menu" => "detail dealer",
                "data_id" => $data->id,
                "mobile_link" => "/DetailDealerTemp",
                "desktop_link" => "/marketing-support/dealer/detail-confirm-dealer/".$data->id,
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
                'as_marketing' => false,
                'status' => $data->status,
                'notifiable_id' => $data->personel_id,
            ];

            $member->notify(new DealerTempNotification($detail));
            $notification = $member->notifications->first();
            $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
            $notification->notification_marketing_group_id = 5;
            $notification->notified_feature = "dealer";
            $notification->notifiable_id = $data->id;
            $notification->personel_id = $data->personel_id;
            $notification->notification_text = $textNotif;
            $notification->mobile_link = $fields['data']['mobile_link'];
            $notification->desktop_link = $fields['data']['desktop_link'];
            $notification->as_marketing = false;
            $notification->status = $data->status;
            $notification->data_id = $data->id;
            $notification->save();
        }

        return OneSignal::sendPush($fields, $textNotif);
    }

    private function approvalSupport()
    {
        $data = $this->dealerTemp;

        $users = User::with(['userDevices', 'permissions'])
            ->whereHas('permissions', function ($q) {
                return $q->whereIn('name', [
                    '(S) Persetujuan Dealer',
                ]);
            })
            ->get();
        
        $userDevices = $users->map(function ($q) {
            return $q->userDevices->map(function ($q) {
                return $q->os_player_id;
            })->toArray();
        })->flatten()->toArray();

        $textNotif = "Pengajuan dealer baru dari ".optional($data->personel)->name." perlu disetujui";
        $fields = [
            "include_player_ids" => $userDevices,
            "data" => [
                "subtitle" => $textNotif,
                "menu" => "detail dealer",
                "data_id" => $data->id,
                "mobile_link" => "/DetailDealerTemp",
                "desktop_link" => "/marketing-support/dealer/agreement-dealer-detail/".$data->id,
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
                'as_marketing' => false,
                'status' => $data->status,
                'notifiable_id' => $data->personel_id,
            ];

            $member->notify(new DealerTempNotification($detail));
            $notification = $member->notifications->first();
            $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
            $notification->notification_marketing_group_id = 5;
            $notification->notified_feature = "dealer";
            $notification->notifiable_id = $data->id;
            $notification->personel_id = $data->personel_id;
            $notification->notification_text = $textNotif;
            $notification->mobile_link = $fields['data']['mobile_link'];
            $notification->desktop_link = $fields['data']['desktop_link'];
            $notification->as_marketing = false;
            $notification->status = $data->status;
            $notification->data_id = $data->id;
            $notification->save();
        }

        return OneSignal::sendPush($fields, $textNotif);
    }

    private function changeConfirmedSupport()
    {
        $data = $this->dealerTemp;

        $users = User::with([   'userDevices', 'permissions'])
            ->whereHas('permissions', function ($q) {
                return $q->whereIn('name', [
                    '(S) Persetujuan Dealer',
                ]);
            })
            ->get();
        
        $userDevices = $users->map(function ($q) {
            return $q->userDevices->map(function ($q) {
                return $q->os_player_id;
            })->toArray();
        })->flatten()->toArray();

        $textNotif = "Pengajuan perubahan dealer ".$data->name." dari ".optional($data->personel)->name." perlu disetujui";
        $fields = [
            "include_player_ids" => $userDevices,
            "data" => [
                "subtitle" => $textNotif,
                "menu" => "detail dealer",
                "data_id" => $data->id,
                "mobile_link" => "/DetailDealerTemp",
                "desktop_link" => "/marketing-support/dealer/agreement-dealer-detail/".$data->id,
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
                'as_marketing' => false,
                'status' => $data->status,
                'notifiable_id' => $data->personel_id,
            ];

            $member->notify(new DealerTempNotification($detail));
            $notification = $member->notifications->first();
            $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
            $notification->notification_marketing_group_id = 5;
            $notification->notified_feature = "dealer";
            $notification->notifiable_id = $data->id;
            $notification->personel_id = $data->personel_id;
            $notification->notification_text = $textNotif;
            $notification->mobile_link = $fields['data']['mobile_link'];
            $notification->desktop_link = $fields['data']['desktop_link'];
            $notification->as_marketing = false;
            $notification->status = $data->status;
            $notification->data_id = $data->id;
            $notification->save();
        }

        return OneSignal::sendPush($fields, $textNotif);
    }


}

