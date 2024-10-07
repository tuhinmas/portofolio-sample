<?php

namespace Modules\DistributionChannel\Listeners;

use Carbon\Carbon;
use Ladumor\OneSignal\OneSignal;
use Modules\Authentication\Entities\User;
use Modules\DistributionChannel\Events\CanceledDispatchOrderEvent;
use Modules\DistributionChannel\Events\DeliveryOrderNotificationEvent;
use Modules\DistributionChannel\Notifications\DeliveryOrderNotification;
use Modules\Personel\Entities\Personel;

class DeliveryOrderNotificationListener
{
    public function handle(DeliveryOrderNotificationEvent $event)
    {
        $deliveryOrder = $event->deliveryOrder;

        if($deliveryOrder->is_promotion == 1 || $deliveryOrder->dispatch_promotion_id){
            if ($deliveryOrder->dispatchPromotion->promotionGoodRequest->event_id != null) {
                $personel = Personel::where('id', $deliveryOrder->dispatchPromotion->promotionGoodRequest->event->personel_id)->select('id','supervisor_id')->first();
            }else{
                $personel = Personel::where('id', $deliveryOrder->dispatchPromotion->promotionGoodRequest->created_by)
                        ->select('id','supervisor_id')
                        ->first();
            }
            $dataId = $deliveryOrder->dispatchPromotion->promotionGoodRequest->id;
            if ($deliveryOrder->status == "send") {
                $message = "surat jalan untuk ".
                    ($deliveryOrder->dispatchPromotion->promotionGoodRequest->event_id != null 
                        ? 
                    $deliveryOrder->dispatchPromotion->promotionGoodRequest->event->name  
                        : 
                    $deliveryOrder->dispatchPromotion->promotionGoodRequest->use_for ." telah dikirim tanggal ".date('d/m/Y', strtotime($deliveryOrder->date_delivery)));
            }else{
                $message = "surat jalan untuk ".
                    ($deliveryOrder->dispatchPromotion->promotionGoodRequest->event_id != null 
                        ? 
                    $deliveryOrder->dispatchPromotion->promotionGoodRequest->event->name  
                        : 
                    $deliveryOrder->dispatchPromotion->promotionGoodRequest->use_for ." dibatalkan tanggal ".date('d/m/Y', strtotime($deliveryOrder->updated_at)));
            }
        }elseif($deliveryOrder->dispatchOrder->invoice->salesOrder->type == 1){
            $personel = Personel::where('id', $deliveryOrder->dispatchOrder->invoice->salesOrder->personel_id)
                    ->select('id','supervisor_id')
                    ->first();

            $dataId = $deliveryOrder->dispatchOrder->invoice->salesOrder->id;
            if ($deliveryOrder->status == "send") {
                $message = "surat jalan untuk ". $deliveryOrder->dispatchOrder->invoice->invoice ." telah dikirim tanggal ".date('d/m/Y', strtotime($deliveryOrder->date_delivery));
            }else{
                $message = "surat jalan untuk ". $deliveryOrder->dispatchOrder->invoice->invoice ." dibatalkan tanggal ".date('d/m/Y', strtotime($deliveryOrder->updated_at));
            }
        }

        if (!$personel) {
            return true;
        }

        $notificationText = $message;
        $mobileLink = "/AddReportReceivingGood";
        $desktopLink = "/marketing-staff/receiving-goods";
        $users = User::with(['userDevices','personel'])
            ->withTrashed()
            ->where(function($q) use($personel){
                return $q->where('personel_id', $personel->id)->when($personel->supervisor_id != null, function($q) use($personel){
                    $q->orWhere('personel_id', $personel->supervisor_id);
                });
            })
            ->get()
            ->map(function($q){
                return [
                    'id' => $q->id,
                    'personel_id' => $q->personel->id,
                    'user_devices' => $q->userDevices->map(function($q){
                        return $q->os_player_id;
                    })->toArray()
                ];
            })->toArray();

        $this->pushNotif($users, [
            'id' => $deliveryOrder->id,
            'subtitle' => 'Direct Sales',
            'notification_text' => $notificationText,
            'mobile_link' => $mobileLink,
            'desktop_link' => $desktopLink
        ]);

        $this->notif($users, $deliveryOrder, $notificationText, $mobileLink, $desktopLink);
    }

    private function pushNotif($users, $data)
    {
        $userDevices = array_reduce($users, function($carry, $item) {
            return array_merge($carry, $item['user_devices']);
        }, []);

        $fields = [
            "include_player_ids" => $userDevices,
            "data" => [
                "subtitle" => $data['subtitle'],
                "menu" => "SalesOrder",
                "data_id" => $data['id'],
                "mobile_link" => $data['mobile_link'],
                "desktop_link" => $data['desktop_link'],
                "notification" => $data['notification_text'],
                "is_supervisor" => false
            ],
            "contents" => [
                "en" => $data['notification_text'],
                "in" => $data['notification_text'],
            ],
            "recipients" => 1,
        ];

        $notif = OneSignal::sendPush($fields, $data['notification_text']);
        return $notif;
    }

    private function notif($users, $deliveryOrder, $notificationText, $mobileLink, $desktopLink)
    {
        foreach (($users ?? []) as $user) {
            $member =  User::withTrashed()->where("id", $user['id'])->first();
            $detail = [
                'personel_id' => $user['personel_id'],
                'notified_feature' => "direct_sales",
                'notification_text' => $notificationText,
                'mobile_link' => $mobileLink,
                'desktop_link' => $desktopLink,
                'data_id' => $deliveryOrder->id,
                'expired_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta'),
                'as_marketing' => true
            ];

            $member->notify(new DeliveryOrderNotification($detail));
            $notification = $member->notifications->first();
            $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
            $notification->notification_marketing_group_id = "13";
            $notification->notified_feature = "delivery_order";
            $notification->notification_text = $notificationText;
            $notification->mobile_link = $mobileLink;
            $notification->desktop_link = $desktopLink;
            $notification->as_marketing = true;
            $notification->status = $deliveryOrder->status;
            $notification->data_id = $deliveryOrder->id;
            $notification->save();
        }
    }
}