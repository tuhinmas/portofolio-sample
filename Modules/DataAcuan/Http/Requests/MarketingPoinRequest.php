<?php

namespace Modules\DataAcuan\Http\Requests;

use Illuminate\Validation\Rule;
use Orion\Http\Requests\Request;

class MarketingPoinRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules(): array
    {
        return [
            "point" => "required|integer",
            "start_date" => "date",
            "end_date" => "date",
            "product_id" => "required",
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
            "point" => "required|integer",
            "start_date" => "date",
            "end_date" => "date",
            "product_id" => "required",
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
