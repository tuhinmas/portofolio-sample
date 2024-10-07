<?php

namespace Modules\DataAcuan\Http\Requests;

use Illuminate\Validation\Rule;
use Orion\Http\Requests\Request;

class PrizeMarketingRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules(): array
    {
        return [
            "year" => "required",
            "prize" => "required",
            "poin" => "required|integer",
            "code" => "required",
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
            "year" => "required",
            "prize" => "required",
            "poin" => "required|integer",
            "code" => "required",
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

