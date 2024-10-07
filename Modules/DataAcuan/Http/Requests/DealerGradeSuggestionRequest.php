<?php

namespace Modules\DataAcuan\Http\Requests;

use Orion\Http\Requests\Request;
use Illuminate\Support\Facades\Route;
use Modules\DataAcuan\Rules\DealerSuggestGradeUniqueGradeRule;
use Modules\DataAcuan\Rules\DealerGradeSuggestionMinimumAmountRule;
use Modules\DataAcuan\Rules\DealerGradeSuggestionProformaMinimumCount;
use Modules\DataAcuan\Rules\Dealer\DealerGradeSuggestionPaymentmethodRule;

class DealerGradeSuggestionRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules(): array
    {
        return [
            "grading_id" => [
                new DealerSuggestGradeUniqueGradeRule($this->suggested_grading_id, $this->valid_from),
                "required",
                "min:1",
                "max:99",
            ],
            "suggested_grading_id" => [
                "required",
                "min:1",
                "max:99"
            ],
            "valid_from" => [
                "required",
                "date_format:Y-m-d H:i:s"
            ],
            "payment_methods" => [
                "required",
                "array",
                "min:1",
                new DealerGradeSuggestionPaymentmethodRule()
            ],
            "payment_methods.*" => [
                "required",
                "distinct"
            ],
            "is_infinite_settle_days" => [
                "required",
                "bool"
            ],
            "maximum_settle_days" => [
                "required",
                "digits_between:0,999999999999999",
            ],
            "proforma_last_minimum_amount" => [
                "required_without:proforma_total_amount",
                "required_if:proforma_total_amount,0",
                "numeric",
                "between:0,9999999999999999.99",
                "nullable",
                new DealerGradeSuggestionMinimumAmountRule($this->proforma_total_amount),
            ],
            "proforma_sequential" => [
                "required_with:proforma_last_minimum_amount",
                "nullable",
                new DealerGradeSuggestionProformaMinimumCount($this->proforma_last_minimum_amount),
            ],
            "proforma_total_amount" => [
                "nullable",
                "required_without:proforma_last_minimum_amount",
                "required_if:proforma_last_minimum_amount,0",
                "numeric",
                "between:0,9999999999999999.99",
                new DealerGradeSuggestionMinimumAmountRule($this->proforma_last_minimum_amount),
            ],
            "proforma_count" => [
                "nullable",
                "required_with:proforma_total_amount",
                new DealerGradeSuggestionProformaMinimumCount($this->proforma_total_amount),
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
        $grade_suggest_id = Route::current()->parameters();

        return [
            "grading_id" => [
                "required",
                "min:1",
                "max:99",
                new DealerSuggestGradeUniqueGradeRule($this->suggested_grading_id, $this->valid_from, $grade_suggest_id),
            ],
            "suggested_grading_id" => [
                "required",
                "min:1",
                "max:99"
            ],
            "valid_from" => [
                "required",
                "date_format:Y-m-d H:i:s"
            ],
            "payment_methods" => [
                "array",
                "min:1",
                new DealerGradeSuggestionPaymentmethodRule()
            ],
            "payment_methods.*" => [
                "distinct"
            ],
            "is_infinite_settle_days" => [
                "required",
                "bool"
            ],
            "maximum_settle_days" => [
                "required",
                "digits_between:0,999999999999999",
            ],
            "proforma_last_minimum_amount" => [
                "numeric",
                "between:1,999999999999999.99",
                "nullable",
                new DealerGradeSuggestionMinimumAmountRule(null, $grade_suggest_id, "proforma_total_amount"),
            ],
            "proforma_sequential" => [
                "digits_between:1,999999999999999",
                "required_with:proforma_last_minimum_amount",
                "nullable",
            ],
            "proforma_total_amount" => [
                "numeric",
                "between:1,999999999999999.99",
                "nullable",
                new DealerGradeSuggestionMinimumAmountRule(null, $grade_suggest_id, "proforma_last_minimum_amount"),
            ],
            "proforma_count" => [
                "digits_between:1,999999999999999",
                "required_with:proforma_total_amount",
                "nullable",
                new DealerGradeSuggestionProformaMinimumCount($this->proforma_total_amount),
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
