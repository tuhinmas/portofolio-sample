<?php

namespace Modules\DataAcuan\Http\Requests;

use Orion\Http\Requests\Request;
use Illuminate\Support\Facades\Route;
use Modules\DataAcuan\Rules\MaxDaysYearUniqueRule;

class MaxDaysReferenceRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules(): array
    {
        return [
            "year" => [
                "required",
                "digits:4",
                new MaxDaysYearUniqueRule($this->maximum_days_for)
            ],
            "maximum_days" => [
                "required",
                "numeric",
                "min:1",
                "max:90",
            ],
            "maximum_days_for" => [
                "required",
                "numeric",
                "min:1",
                "max:14",
            ],
            "description" => [
                "nullable",
                "string",
                "min:3",
                "max:255",
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
        /* get parameter of route */
        $reference_id = Route::current()->parameters();
        
        return [
            "year" => [
                "digits:4",
                new MaxDaysYearUniqueRule($this->maximum_days_for, $reference_id["maximum_days_reference"])
            ],
            "maximum_days" => [
                "numeric",
                "min:1",
                "max:90",
            ],
            "maximum_days_for" => [
                "required_with:year",
                "numeric",
                "min:1",
                "max:255",
            ],
            "description" => [
                "nullable",
                "string",
                "min:3",
                "max:255",
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
