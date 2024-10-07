<?php

namespace Modules\DataAcuan\Http\Requests;

use Illuminate\Support\Facades\Route;
use Modules\DataAcuan\Rules\Fee\FeePositionHistoryDateStartRule;
use Modules\DataAcuan\Rules\Fee\FeePositionHistoryRule;
use Orion\Http\Requests\Request;

class FeePositionHistoryRequest extends Request
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
                new FeePositionHistoryDateStartRule,
            ],
            "fee_position" => [
                "required",
                new FeePositionHistoryRule,
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
        $history_id = Route::current()->parameters();
        return [
            "date_start" => [
                "date",
                new FeePositionHistoryDateStartRule($history_id),
            ],
            "fee_position" => [
                new FeePositionHistoryRule,
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
