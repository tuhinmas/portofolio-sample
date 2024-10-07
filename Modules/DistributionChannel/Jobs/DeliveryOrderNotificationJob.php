<?php

namespace Modules\DistributionChannel\Jobs;

use App\Jobs\OneSignalJob;
use App\Models\User;
use App\Models\UserDevice;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Ladumor\OneSignal\OneSignal;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\DistributionChannel\Events\DeliveryOrderNotificationEvent;
use Modules\DistributionChannel\Notifications\DeliveryOrderNotification;
use Modules\Personel\Entities\Personel;

class DeliveryOrderNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $deliveryOrder;

    public $timeout = 500;

    public $tries = 2;

    public function __construct($deliveryOrder)
    {
        $this->deliveryOrder = $deliveryOrder;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DeliveryOrderNotificationEvent::dispatch($this->deliveryOrder);
    }
}