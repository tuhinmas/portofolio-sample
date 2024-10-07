<?php

namespace Modules\KiosDealer\Rules;

use Illuminate\Contracts\Validation\Rule;
use Modules\KiosDealer\Entities\DealerTemp;

class DealerTempCheckOriginRule implements Rule
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
        $dealer_temp = DealerTemp::query()
            ->where("dealer_id", $value)
            ->whereNotIn("status", ["filed rejected", "change rejected"])
            ->first();

            if ($dealer_temp) {
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
        return 'can not store, dealer is already has draft on dealer temp';
    }
}
