<?php

namespace Modules\KiosDealer\Rules;

use Illuminate\Contracts\Validation\Rule;
use Modules\KiosDealer\Entities\SubDealer;

class SubDealerLongitudeRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($sub_dealer_id, $longitude)
    {
        $this->sub_dealer_id = $sub_dealer_id;
        $this->longitude = $longitude;
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
        $sub_dealer = SubDealer::findOrFail($this->sub_dealer_id["sub_dealer"]);
        if (!$sub_dealer->longitude && empty($this->longitude)) {
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
        return 'you need to send longitude, this sub dealer doesn\'t have longitude at all';
    }
}
