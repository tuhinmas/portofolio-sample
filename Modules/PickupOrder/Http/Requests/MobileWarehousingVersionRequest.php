<?php

namespace Modules\PickupOrder\Http\Requests;

use Orion\Http\Requests\Request;
use Modules\PickupOrder\Rules\WarehouseApkLinkRule;

class MobileWarehousingVersionRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function commonRules(): array
    {
        return [
            "version" => "required",
            "environment" => "required",
            "note" => "required",
            "link" => [
                "required",
                new WarehouseApkLinkRule,
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
