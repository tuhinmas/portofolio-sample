<?php

namespace Modules\DataAcuan\Http\Requests;

use Illuminate\Validation\Rule;
use Orion\Http\Requests\Request;

class BudgetRuleRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules(): array
    {
        return [
            "max_budget" => "required|numeric",
            "type_budget" => "required",
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
            "max_budget" => "required|numeric",
            "type_budget" => "required",
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
