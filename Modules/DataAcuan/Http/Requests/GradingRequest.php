<?php

namespace Modules\DataAcuan\Http\Requests;

use App\Traits\ResponseHandler;
use Illuminate\Validation\Rule;
use Modules\DataAcuan\Rules\Grading\GradingMaxPaymentrRule;
use Modules\DataAcuan\Rules\Grading\GradingMaxUnsettleOrderRule;
use Orion\Http\Requests\Request;

class GradingRequest extends Request
{
    use ResponseHandler;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules(): array
    {
        return [
            "name" => [
                "required",
                Rule::unique('gradings', 'name')
                    ->whereNull('deleted_at'),
            ],
            "maximum_payment_days" => [
                "nullable",
                new GradingMaxPaymentrRule,
            ],
            "max_unsettle_proformas" => [
                "nullable",
                new GradingMaxUnsettleOrderRule,
            ],
        ];
    }

    public function updateRules(): array
    {
        $grading_id = request()->route()->parameters();
        return [
            "name" => [
                "max:255",
                Rule::unique('gradings', 'name')
                    ->ignore($grading_id["grading"], 'id')
                    ->whereNull('deleted_at'),
            ],
            "maximum_payment_days" => [
                "nullable",
                new GradingMaxPaymentrRule,
            ],
            "max_unsettle_proformas" => [
                "nullable",
                new GradingMaxUnsettleOrderRule,
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
