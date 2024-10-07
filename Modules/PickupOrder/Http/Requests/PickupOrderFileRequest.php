<?php

namespace Modules\PickupOrder\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\PickupOrder\Rules\PickupOrderFileLinkRule;
use Orion\Http\Requests\Request;

class PickupOrderFileRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules(): array
    {
        return [
            "pickup_order_id" => "required",
            "caption" => [
                "required",
            ],
            "attachment" => [
                "required",
                new PickupOrderFileLinkRule,
            ],
        ];
    }

    public function updateRules(): array
    {
        return [
            "attachment" => [
                new PickupOrderFileLinkRule,
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
