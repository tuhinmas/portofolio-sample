<?php

namespace Modules\DataAcuan\Http\Requests;

use Orion\Http\Requests\Request;
use Illuminate\Support\Facades\Route;
use Modules\DataAcuan\Rules\Fee\FeeFollowUpHistoryRule;
use Modules\DataAcuan\Rules\Fee\FeeFollowUpHistoryDateStartRule;

class FeeFollowUpHistoryRequest extends Request
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
                new FeeFollowUpHistoryDateStartRule
            ],
            "fee_follow_up" => [
                "required",
                "array",
                new FeeFollowUpHistoryRule,
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
                new FeeFollowUpHistoryDateStartRule($history_id)
            ],
            "fee_follow_up" => [
                "required",
                "array",
                new FeeFollowUpHistoryRule,
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
