<?php

namespace Modules\DataAcuan\Http\Requests;

use Orion\Http\Requests\Request;

class MaximumSettleDayRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules(): array
    {
        return [
            "personel_id" => [
                "required",
                "max:255",
            ],
            "max_settle_for" => [
                "required",
                "string",
                "max:255",
            ],
            "year" => [
                "required",
                "digits:4",
                "integer",
                "min:2000",
                "max:" . (date('Y') + 4),
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
        return [
            "personel_id" => [
                "max:255",
            ],
            "max_settle_for" => [
                "string",
                "max:255",
            ],
            "year" => [
                "digits:4",
                "integer",
                "min:2000",
                "max:" . (date('Y') + 4),
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
