<?php

namespace Modules\KiosDealer\Listeners;

use Carbon\Carbon;
use Modules\KiosDealer\Entities\Store;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Ladumor\OneSignal\OneSignal;
use Modules\Authentication\Entities\User;
use Modules\KiosDealer\Entities\CoreFarmer;
use Modules\KiosDealer\Events\StoreTempConfirmationEvent;
use Modules\KiosDealer\Jobs\StoreTempNotificationJob;
use Modules\KiosDealer\Notifications\StoreTempNotification;

class StoreTempConfirmationListener
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
    public function handle(StoreTempConfirmationEvent $event)
    {
        StoreTempNotificationJob::dispatch($event->store_temp);
        if ($event->store_temp->status == "filed") {
            $store = new Store;
            $store = $store->fill(collect($event->store_temp)->except(["id", "created_at", "updated_at", "store_id", "change_note"])->toArray());
            $store->status = "accepted";
            $store->status_color = "000000";
            $store->save();
            if (count($event->store_temp->core_farmer) > 0) {
                foreach ($event->store_temp->core_farmer as $core_farmer) {
                    $core_farmer_fix = new CoreFarmer;
                    $core_farmer_fix = $core_farmer_fix->fill(collect($core_farmer)->except(["id", "core_farmer_id", "store_temp_id", "created_at", "updated_at"])->toArray());
                    $core_farmer_fix->store_id = $store->id;
                    $core_farmer_fix->save();
                }
            }
            $event->store_temp->delete();

            $users = User::with(['userDevices', 'permissions'])
                ->withTrashed()
                ->where("personel_id", $store->personel_id)
                ->get();

            $userDevices = $users->map(function ($q) {
                return $q->userDevices->map(function ($q) {
                    return $q->os_player_id;
                })->toArray();
            })->flatten()->toArray();

            $personelName = $store->personel?->name ?? null;

            $textNotif = "Pengajuan kios $store->name telah disetujui oleh support";
            $fields = [
                "include_player_ids" => $userDevices,
                "data" => [
                    "subtitle" => "Kios",
                    "menu" => "Kios",
                    "data_id" => $store->id,
                    "mobile_link" => "",
                    "desktop_link" => "/marketing-staff/kios-detail-data/" . $store->id,
                    "notification" => $textNotif,
                    "is_supervisor" => false,
                ],
                "contents" => [
                    "en" => $textNotif,
                    "in" => $textNotif,
                ],
                "recipients" => 1,
            ];

            $detail = [
                'notification_marketing_group_id' => 8,
                'personel_id' => $store->personel_id,
                'notified_feature' => "kios",
                'notification_text' => "Pengajuan kios $store->name telah disetujui oleh support",
                'mobile_link' => "",
                'desktop_link' => "/marketing-staff/kios-detail-data/" . $store->id,
                'data_id' => $store->id,
                'expired_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta'),
                'as_marketing' => true,
                'status' => $store->status,
                'notifiable_id' => $store->personel_id,
            ];

            $member =  User::withTrashed()->where("personel_id", $store->personel_id)->first();

            if ($member) {
                // dd($member);
                $member->notify(new StoreTempNotification($detail));
                $notification = $member->notifications->first();
                $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
                $notification->notification_marketing_group_id = "8";
                $notification->notified_feature = "kios";
                // $notification->notifiable_id = $member->id;
                $notification->personel_id = $store->personel_id;
                $notification->notification_text = "Pengajuan kios ".$store->name." telah disetujui oleh support";
                $notification->mobile_link = "";
                $notification->desktop_link = "/marketing-staff/kios-detail-data/" . $store->id;
                $notification->as_marketing = true;
                $notification->status = "accepted";
                $notification->data_id = $store->id;
                $notification->save();
            }


            OneSignal::sendPush($fields, $textNotif);

            return $store;
        } elseif ($event->store_temp->status == "submission of changes") {
            $store = Store::findOrFail($event->store_temp->store_id);
            $store = $store->fill(collect($event->store_temp)->except(["id", "created_at", "updated_at", "store_id", "change_note"])->toArray());
            $store->status = "accepted";
            $store->status_color = "000000";
            $store->save();

            /**
             * delete core farmer fix
             */
            CoreFarmer::query()
                ->where("store_id", $store->id)
                ->delete();

            if (count($event->store_temp->core_farmer) > 0) {
                foreach ($event->store_temp->core_farmer as $core_farmer) {
                    $core_farmer_fix = new CoreFarmer;
                    $core_farmer_fix = $core_farmer_fix->fill(collect($core_farmer)->except(["id", "core_farmer_id", "store_temp_id", "created_at", "updated_at"])->toArray());
                    $core_farmer_fix->store_id = $store->id;
                    $core_farmer_fix->save();
                }
            }

            $event->store_temp->delete();
            return $store;
        }
    }
}
