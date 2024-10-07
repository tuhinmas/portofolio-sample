<?php

namespace Modules\DistributionChannel\Http\Requests;

use Orion\Http\Requests\Request;

class ListDispatchOrderRequest extends Request
{
        /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules(): array
    {
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function updateRules(): array
    {
        return [
            "status" => "required",
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
