<?php

namespace Modules\PickupOrder\Http\Requests;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\FormRequest;
use Modules\PickupOrder\Entities\PickupOrder;
use Illuminate\Contracts\Validation\Validator;
use Modules\PickupOrder\Rules\PickupOrderLoadedRule;
use Illuminate\Http\Exceptions\HttpResponseException;

class PickupOrderRequest extends Request
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
            "warehouse_id" => "required",
            "driver_id" => "required",
            "delivery_date" => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    $formattedDate = date('Y-m-d H:i', strtotime($value));
                    $existingRecords = PickupOrder::where('delivery_date', 'like', $formattedDate.'%')
                        ->where('warehouse_id', $this->warehouse_id)
                        ->where('driver_id', $this->driver_id)
                        ->count();
                    if ($existingRecords > 0) {
                        $fail("The $attribute has already been taken.");
                    }
                },
            ],
        ];
    }

    public function updateRules(): array
    {
        $pickup_order_id = Route::current()->parameters();
        return [
            "status" => [
                new PickupOrderLoadedRule($pickup_order_id)
            ]
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
