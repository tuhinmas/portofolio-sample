<?php

namespace Modules\Personel\Rules\Marketing;

use Illuminate\Contracts\Validation\Rule;
use Modules\Personel\Entities\MarketingFeePayment;

class MarketingFeePaymenAmounttRule implements Rule
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
        $reference_number = MarketingFeePayment::query()
            ->where("reference_number", $value)
            ->first();

        if ($reference_number) {
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
        return 'reference number harus unik';
    }
}
