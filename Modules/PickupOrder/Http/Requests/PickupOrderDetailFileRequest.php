<?php

namespace Modules\PickupOrder\Http\Requests;

use Illuminate\Validation\Rule;
use Orion\Http\Requests\Request;
use Modules\PickupOrder\Rules\PickupOrderDeatilFileLinkRule;
use Modules\PickupOrder\Rules\PickupOrderDetailFileTypeRule;

class PickupOrderDetailFileRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules(): array
    {
        return [
            "pickup_order_detail_id" => [
                "required",
            ],
            "attachment" => [
                "required",
                new PickupOrderDeatilFileLinkRule,
            ],
            "type" => [
                Rule::in(['load', 'unload']),
                new PickupOrderDetailFileTypeRule($this->pickup_order_detail_id)
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

    public function messages()
    {
        return [
            'type.in' => 'Invalid value of type, only: load, unload.',
        ];
    }
}
