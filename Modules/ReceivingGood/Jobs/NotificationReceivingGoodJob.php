<?php

namespace Modules\ReceivingGood\Jobs;

use App\Models\UserDevice;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Ladumor\OneSignal\OneSignal;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\Personel\Entities\Personel;
use Modules\ReceivingGood\Entities\ReceivingGood;

class NotificationReceivingGoodJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $receivingGood;

    public $timeout = 500;

    public $maxExceptions = 2;

    public function __construct(ReceivingGood $receivingGood)
    {
        $this->receivingGood = $receivingGood;
    }

    public function handle()
    {
        $playerIds = player_id_by_permissions([
            "(S) Surat Jalan",
            "(B) Detail Surat Jalan",
            "(B) Cetak Surat Jalan"
        ]);

        if (!empty($this->receivingGood->receivedBy)) {
            if ($this->receivingGood->deliveryOrder->dispatch_promotion_id != null) {
                $for = $this->receivingGood->deliveryOrder->dispatchPromotion->promotionGoodRequest->event_id != null 
                    ? 
                $this->receivingGood->deliveryOrder->dispatchPromotion->promotionGoodRequest->event->name  
                    : 
                $this->receivingGood->deliveryOrder->dispatchPromotion->promotionGoodRequest->use_for;
            }else{
                $for = $this->receivingGood->deliveryOrder->dispatchOrder->invoice->invoice_proforma_number;
            }
    
            $message = "surat jalan untuk ".$for." diterima oleh ".$this->receivingGood->receivedBy->name." tanggal ".date('d/m/Y', strtotime($this->receivingGood->created_at));
                
            $fields = [
                "include_player_ids" => $playerIds->toArray(),
                "data" => [
                    "subtitle" => $message,
                    "menu" => "event",
                    "data_id" => $this->receivingGood->id,
                    "app_url" => "",
                ],
                "contents" => [
                    "en" => $message,
                    "in" => $message,
                ],
                "recipients" => 1,
            ];
    
            $notif = OneSignal::sendPush($fields, $message);
            $notif;
        }

    }
}