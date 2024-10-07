<?php

namespace Modules\DataAcuan\Rules\Dealer;

use Illuminate\Contracts\Validation\Rule;

class DealerBenefitOldPriceUsageRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($old_price_usage, $limit)
    {
        $this->old_price_usage = $old_price_usage;
        $this->limit = $limit;
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
        if ($value) {
            if (gettype($value) != "integer") {
               return false;
            }
        }
        if ($this->old_price_usage) {
            if (!$value && !$this->limit) {
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
        return 'at leat one limit must set';
    }
}
