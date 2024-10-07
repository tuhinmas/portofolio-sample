<?php

namespace Modules\DataAcuan\Rules;

use Illuminate\Contracts\Validation\Rule;

class DealerGradeSuggestionProformaMinimumCount implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($amount = null)
    {
        $this->amount = $amount;
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
        if ($this->amount) {
            if ($value <= 0) {
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
        return "minimal proforma berturut adalah 1";
    }
}
