<?php

namespace Modules\DistributionChannel\Http\Requests;

use App\Traits\ResponseHandler;
use Illuminate\Validation\Rule;
use Orion\Http\Requests\Request;
use Illuminate\Support\Facades\Route;
use Modules\DistributionChannel\Rules\DeliveryOrderRule;
use Modules\DistributionChannel\Rules\DeliveryOrderPromotionUniqueRule;
use Modules\DistributionChannel\Rules\DeliveryOrderUpdateDateDeliveryRule;

class DeliveryOrderRequest extends Request
{
    use ResponseHandler;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules(): array
    {
        return [
            "date_delivery" => "required",
            "dispatch_order_id" => [
                function ($attribute, $value, $fail) {
                    if (!is_null($value)) {
                        $deliveryOrderRule = new DeliveryOrderRule();

                        if (!$deliveryOrderRule->passes($attribute, $value)) {
                            $fail('The delivery order rule failed.');
                        }
                    }
                },
            ],
            "dispatch_promotion_id" => [
                new DeliveryOrderPromotionUniqueRule
            ],
            "delivery_order_number" => [
                Rule::unique("delivery_orders")
                    ->where("status", "send")
                    ->where("delivery_order_number", $this->delivery_order_number)
                    ->whereNull("deleted_at"),
            ],
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function updateRules(): array
    {
        /* get parameter of route */
        $delivery_order_id = Route::current()->parameters();

        return [
            "dispatch_order_id" => "max:255",
            "delivery_order_number" => [
                Rule::unique("delivery_orders")
                    ->ignore($delivery_order_id["delivery_order"])
                    ->where("delivery_order_number", $this->delivery_order_number)
                    ->whereNull("deleted_at"),
            ],
            "date_delivery" => [
                function ($attribute, $value, $fail) use ($delivery_order_id) {
                    if (!is_null($value)) {
                        $deliveryOrderRule = new DeliveryOrderUpdateDateDeliveryRule($delivery_order_id);
                        if (!$deliveryOrderRule->passes($attribute, $value)) {
                            $fail('The delivery order rule failed.');
                        }
                    }
                },
            ],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
