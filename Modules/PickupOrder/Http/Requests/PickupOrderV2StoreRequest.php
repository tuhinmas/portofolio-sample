<?php

namespace Modules\PickupOrder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\CSMS\Constants\Company\MinningServiceType;
use Modules\PickupOrder\Rules\PickupOrderV2DispatchRule;

class PickupOrderV2StoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            "warehouse_id" => "required",
            "delivery_date" => "required",
            "dispatch_id" => [
                "required",
                new PickupOrderV2DispatchRule,
            ],
        ];
    }

    public function attributes(): array
    {
        return [];
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