<?php

namespace Modules\PickupOrder\Console;

use Illuminate\Console\Command;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\PromotionGood\Entities\DispatchPromotion;

class FixDispatchStatusCommand extends Command
{
    protected $name = 'pickup-order:fix-dispatch';

    protected $description = 'fix dispatch status';

    public function handle()
    {
        DispatchOrder::query()
            ->whereNull("status")
            ->whereNotNull("invoice_id")
            ->lazy()
            ->each(function ($q) {
                if (optional($q->deliveryOrder)->receivingGoodHasReceived) {
                    $status = "received";
                } elseif ($q->deliveryOrder) {
                    $status = "delivered";
                } elseif ($q->is_active == 0) {
                    $status = "canceled";
                } else {
                    $status = "planned";
                }

                $q->status = $status;
                $q->save();

                dump($q->status);
            });

        DispatchPromotion::query()
            ->whereNull("status")
            ->whereNotNull("delivery_address_id")
            ->lazy()
            ->each(function ($q) {
                if (optional($q->deliveryOrder)->receivingGoodHasReceived) {
                    $status = "received";
                } elseif ($q->deliveryOrder) {
                    $status = "delivered";
                } elseif ($q->is_active == 0) {
                    $status = "canceled";
                } else {
                    $status = "planned";
                }

                $q->status = $status;
                $q->save();
                dump($q->status);
            });
    }
}
