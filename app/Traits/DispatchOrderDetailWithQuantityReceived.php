<?php

namespace App\Traits;

use Modules\DistributionChannel\Entities\DispatchOrderDetail;
use Modules\PickupOrder\Entities\DeliveryPickupOrder;
use Modules\PromotionGood\Entities\DispatchPromotionDetail;
use Modules\PromotionGood\Entities\PromotionGoodDispatchOrder;
use Modules\ReceivingGood\Entities\ReceivingGood;
use Modules\ReceivingGood\Entities\ReceivingGoodDetail;

trait DispatchOrderDetailWithQuantityReceived
{
    public function dispatchOrderDetailWithQuantityReceived($promotion_good_id = null, $promotion_good_request_id = null)
    {
        $dispatchPromotion = DispatchPromotionDetail::with('dispatchPromotion.deliveryOrder.receivingGoods')
            ->where("promotion_good_id", $promotion_good_id)
            ->whereHas("dispatchPromotion", function($q){
                $q->where("is_active", 1)->orWhere("status", "!=", "canceled");
            })
            ->get()
            ->map(function ($q) {
                if (!$q->dispatchPromotion->deliveryOrder) {
                    return $q->planned_quantity_packet_to_send ?? $q->planned_quantity_unit;
                }else{
                    $deliveryOrder = $q->dispatchPromotion->deliveryOrder->id;
                    $sumReceivingGoodDetail = ReceivingGoodDetail::whereHas('receivingGood', function ($q) use ($deliveryOrder) {
                        return $q->where('delivery_order_id', $deliveryOrder)->where('delivery_status', '2');
                    })
                    ->where('status', 'delivered')
                    ->where('promotion_good_id', $q->promotion_good_id)
                    ->sum('quantity');
    
                    if (empty($q->dispatchPromotion->deliveryOrder->receivingGoods)) {
                        $total = $q->planned_quantity_unit;
                    } elseif ($sumReceivingGoodDetail) {
                        if ($q->planned_quantity_unit == $sumReceivingGoodDetail) {
                            $total = $q->planned_quantity_unit;
                        } else {
                            $total = $sumReceivingGoodDetail;
                        }
                    } else {
                        $total = $q->planned_quantity_unit;
                    }
    
                    return $total;
                }

            });
            
            
        return $dispatchPromotion->sum();
    }

    public function dispatchOrderDetailWithQuantityRemaining($promotion_good_id = null, $promotion_good_request_id = null)
    {
        $remainingquantity = $this->dispatchOrderDetailWithQuantityReceived();
    }
}
