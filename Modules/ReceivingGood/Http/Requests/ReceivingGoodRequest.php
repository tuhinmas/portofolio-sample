<?php

namespace Modules\ReceivingGood\Http\Requests;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\ReceivingGood\Rules\ReceivingGoodDeliveryStatusRule;

class ReceivingGoodRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules():array
    {
        return [
            "delivery_order_id" => "required|max:255",
            "delivery_status" => [
                new ReceivingGoodDeliveryStatusRule($this)
            ]
        ];
    }

    public function updateRules():array
    {
        $receiving_good_id = Route::current()->parameters();
        return [
            "delivery_order_id" => "max:255",
            "delivery_status" => [
                new ReceivingGoodDeliveryStatusRule($this, $receiving_good_id)
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
