<?php

namespace Modules\DataAcuan\Http\Requests;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Support\Facades\Route;
use Modules\DataAcuan\Rules\BenefitRule;
use Modules\DataAcuan\Entities\DealerBenefit;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\DataAcuan\Rules\Dealer\DealerBenefitGradingRule;
use Modules\DataAcuan\Rules\Dealer\DealerBenefitDiscountRule;
use Modules\DataAcuan\Rules\Dealer\DealerBenefitOldPriceUsageRule;

class DealerBenefitRequest extends Request
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
            "grading_id" => [
                "required",
                "max:36",
                new DealerBenefitGradingRule(),
            ],
            "payment_method_id" => "required|max:255",
            "agency_level_id" => [
                "required",
                "array"
            ],
            "agency_level_id.*" => [
                "required",
                "distinct",
                "min:32",
                "max:36"
            ],
            "benefit_discount" => [
                "required",
                "array",
                new DealerBenefitDiscountRule()
            ],
            "old_price_usage" => [
                "bool",
                "required_with:old_price_usage_limit",
                "required_with:old_price_days_limit",
            ],
            "old_price_usage_limit" => [
                "numeric",
                new DealerBenefitOldPriceUsageRule($this->old_price_usage, $this->old_price_days_limit),
            ],
            "old_price_days_limit" => [
                "numeric",
                new DealerBenefitOldPriceUsageRule($this->old_price_usage, $this->old_price_usage_limit),
            ],
            "start_period" => [
                "nullable",
                "date"
            ],
            "end_period" => [
                "nullable",
                "date"
            ]
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
        $benefit_grade_id = Route::current()->parameters();

        return [
            "grading_id" => [
                "max:36",
                new DealerBenefitGradingRule($benefit_grade_id),
            ],
            "benefit_discount" => [
                "array",
                new DealerBenefitDiscountRule()
            ],
            "payment_method_id" => "max:255",
            "agency_level_id" => [
                "max:255"
            ],
            "old_price_usage" => [
                "bool"
            ],
            "old_price_usage" => [
                "bool",
                "required_with:old_price_usage_limit",
                "required_with:old_price_days_limit",
            ],
            "old_price_usage_limit" => [
                new DealerBenefitOldPriceUsageRule($this->old_price_usage, $this->old_price_days_limit),
            ],
            "old_price_days_limit" => [
                new DealerBenefitOldPriceUsageRule($this->old_price_usage, $this->old_price_usage_limit),
            ],
            "start_period" => [
                "nullable",
                "date"
            ],
            "end_period" => [
                "nullable",
                "date"
            ]
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
