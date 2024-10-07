<?php

namespace Modules\PickupOrder\Rules;

use Illuminate\Contracts\Validation\Rule;

class PickupDetailActualQuantityRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($request)
    {
        $this->request = $request;
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
        if ($this->request->has("quantity_unit_load")) {
            return $value <= $this->request->quantity_unit_load;
        }

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'actual quantity load can not higher then quantity load';
    }
}
