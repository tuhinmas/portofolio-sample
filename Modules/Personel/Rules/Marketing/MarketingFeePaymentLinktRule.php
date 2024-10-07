<?php

namespace Modules\Personel\Rules\Marketing;

use Illuminate\Contracts\Validation\Rule;

class MarketingFeePaymentLinktRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
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
            if (app()->isLocal() || app()->environment('testing')) {
                return collect(explode("/", $value))->contains("staging");
            }
            else {
                return collect(explode("/", $value))->contains(app()->environment());
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
        return 'link harus sesuai environment, public/marketing/fee-payment/(staging / production)';
    }
}
