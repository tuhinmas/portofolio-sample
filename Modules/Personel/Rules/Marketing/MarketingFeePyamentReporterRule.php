<?php

namespace Modules\Personel\Rules\Marketing;

use Illuminate\Contracts\Validation\Rule;

class MarketingFeePyamentReporterRule implements Rule
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
        $marketing = auth()->user()->profile;
        if ($marketing) {
            if (in_array($marketing?->position->name, marketing_positions())) {
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
        return 'Marketing tidak dapat melaporkan pembayaran fee';
    }
}
