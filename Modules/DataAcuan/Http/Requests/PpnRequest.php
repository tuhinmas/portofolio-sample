<?php

namespace Modules\DataAcuan\Http\Requests;

use Illuminate\Validation\Rule;
use Orion\Http\Requests\Request;

class PpnRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules(): array
    {
        return [
            "ppn" => "required|numeric",
            "period_date" => [
                "required",
                Rule::unique('ppn')
                    ->where('ppn', $this->ppn)
                    ->where('period_date', $this->period_date)
                    ->whereNull("deleted_at"),
            ],
            "user_id" => "required",
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
            "ppn" => "required|numeric",
            "period_date" => [
                "required",
                Rule::unique('ppn')
                    ->where('ppn', $this->ppn)
                    ->where('period_date', $this->period_date),
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
