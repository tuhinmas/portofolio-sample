<?php

namespace Modules\DistributionChannel\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\PickupOrder\Constants\PickupOrderStatus;
use Modules\PickupOrder\Entities\DeliveryPickupOrder;

class DeliveryOrderUpdateDateDeliveryRule implements Rule
{

    protected $delivery_order;

    public function __construct($delivery_order = null)
    {
        $this->delivery_order = $delivery_order;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $deliveryOrderId = $this->delivery_order['delivery_order'];
        $deliveryOrder = DeliveryOrder::find($deliveryOrderId);
        if ($deliveryOrder->is_promotion == 1) {
            $deliveryPickupOrder = DeliveryPickupOrder::where('delivery_order_id', $deliveryOrderId)->first();
            if ($deliveryPickupOrder) {
                if ($deliveryPickupOrder->pickupOrder->status == PickupOrderStatus::SENDING || $deliveryOrder->status == 'canceled') {
                    return false;
                }
            }else{
                if ($deliveryOrder->status == 'canceled') {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'delivery order has been there';
    }
}
