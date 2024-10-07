<?php

namespace Modules\DataAcuan\Http\Requests;

use Orion\Http\Requests\Request;
use Illuminate\Support\Facades\Route;
use Modules\DataAcuan\Rules\Fee\FeeFollowUpDaysRule;

class FeeFollowUpRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules(): array
    {
        return [
            "follow_up_days" => [
                "required",
                "max:255",
                new FeeFollowUpDaysRule,
            ],
            "fee" => "required|max:255",
            "settle_days" => "required|max:255",
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function updateRules(): array
    {
        $follow_up_id = Route::current()->parameters();

        return [
            "follow_up_days" => [
                "required",
                "max:255",
                new FeeFollowUpDaysRule($follow_up_id),
            ],
            "fee" => "required|max:255",
            "settle_days" => "required|max:255",
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
