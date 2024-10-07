<?php

namespace Modules\DataAcuan\Rules\Grading;

use Illuminate\Contracts\Validation\Rule;

class GradingMaxPaymentrRule implements Rule
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
        return 'Minimal lama pembayaran grading adalah 0 dan maksimal tanpa batas';
    }
}
