<?php

namespace Modules\DataAcuan\Rules\Grading;

use Illuminate\Contracts\Validation\Rule;

class GradingMaxUnsettleOrderRule implements Rule
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

        /* minimum paymenbt days is 0 */
        if ($value < 0) {
            return false;
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
        return 'Minimal jumlah proforma yang bisa dikredit adalah 0';
    }
}
