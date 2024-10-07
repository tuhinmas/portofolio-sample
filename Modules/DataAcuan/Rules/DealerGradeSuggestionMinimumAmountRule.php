<?php

namespace Modules\DataAcuan\Rules;

use Illuminate\Contracts\Validation\Rule;
use Modules\DataAcuan\Entities\DealerGradeSuggestion;

class DealerGradeSuggestionMinimumAmountRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($amount = null, $grade_suggest_id = null, $attribute_of_amount = null)
    {
        $this->amount = $amount;
        $this->grade_suggest_id = $grade_suggest_id;
        $this->attribute_of_amount = $attribute_of_amount;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if ($this->amount <= 0 && $value <= 0) {
            return false;
        }

        if ($this->grade_suggest_id) {
            $suggestion = DealerGradeSuggestion::findOrFail($this->grade_suggest_id["dealer_grade_suggestion"]);
            $attribute = $this->attribute_of_amount;
            if ($suggestion->$attribute <= 0 && $value <= 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'tidak boleh membuat semua minimal proforma ke 0';
    }
}
