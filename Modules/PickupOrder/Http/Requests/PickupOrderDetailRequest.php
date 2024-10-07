<?php

namespace Modules\PickupOrder\Http\Requests;

use App\Traits\ResponseHandler;
use Illuminate\Validation\Rule;
use Orion\Http\Requests\Request;
use Illuminate\Support\Facades\Route;
use Modules\PickupOrder\Rules\PickupOrderDetailIsLoadedRule;
use Modules\PickupOrder\Rules\PickupDetailActualQuantityRule;

class PickupOrderDetailRequest extends Request
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
            "pickup_order_id" => [
                "required",
            ],
            "product_name" => [
                "required",
                "alpha",
            ],
            "type" => [
                "required",
                "alpha",
            ],
            "quantity_unit_load" => [
                "required",
            ],
            "quantity_actual_load" => [
                "required",
                new PickupDetailActualQuantityRule($this)
            ],
            "unit" => [
                "required",
                "alpha",
            ],
            "total_weight" => [
                "required",
            ],
            "estimate_weight" => [
                "required",
            ],
            "detail_type" => [
                "required",
                Rule::in("dispatch_promotion", "dispatch_order"),
            ],
            "weight" => [
                "required",
            ],
            "is_loaded" => [
                "required",
                "boolean",
                Rule::in(false, 0, "0"),

            ],
        ];
    }

    public function updateRules(): array
    {
        $pickup_order_detail_id = Route::current()->parameters();
        return [
            "is_loaded" => [
                "boolean",
                new PickupOrderDetailIsLoadedRule($pickup_order_detail_id, $this),
            ],
            "pickup_order_id" => [
            ],
            "product_name" => [
                "alpha",
            ],
            "type" => [
                "alpha",
            ],
            "quantity_unit_load" => [
            ],
            "quantity_actual_load" => [
            ],
            "unit" => [
                "alpha",
            ],
            "total_weight" => [
            ],
            "estimate_weight" => [
            ],
            "detail_type" => [
                Rule::in("dispatch_promotion", "dispatch_order"),
            ],
            "weight" => [
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

    public function messages()
    {
        return [
            "detail_type.in" => "Invalid value for detail_type, only dispatch_promotion, dispatch_order",
        ];
    }
}
