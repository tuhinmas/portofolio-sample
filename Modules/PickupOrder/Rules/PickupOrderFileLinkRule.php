<?php

namespace Modules\PickupOrder\Rules;

use Illuminate\Contracts\Validation\Rule;

class PickupOrderFileLinkRule implements Rule
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
        $env = app()->environment();
        return str_contains($value, "/public/pickup-order/file/$env");
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'link harus sesuai environment, /public/pickup-order/file/(staging/production/local)';
    }
}
