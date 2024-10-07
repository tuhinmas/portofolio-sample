<?php

namespace Modules\DataAcuan\Http\Requests;

use Orion\Http\Requests\Request;
use Illuminate\Support\Facades\Route;
use Modules\DataAcuan\Rules\Fee\StatusFeeHistoryRule;
use Modules\DataAcuan\Rules\Fee\StatusFeeHistoryDateStartRule;

class StatusFeeHistoryRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules(): array
    {
        return [
            "date_start" => [
                "required",
                "date",
                new StatusFeeHistoryDateStartRule,
            ],
            "status_fee" => [
                "required",
                "array",
                new StatusFeeHistoryRule
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
        $history_id = Route::current()->parameters();
        return [
            "date_start" => [
                "required",
                "date",
                new StatusFeeHistoryDateStartRule($history_id),
            ],
            "status_fee" => [
                "required",
                "array",
                new StatusFeeHistoryRule
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
