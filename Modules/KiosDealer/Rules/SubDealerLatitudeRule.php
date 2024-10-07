<?php

namespace Modules\KiosDealer\Rules;

use Illuminate\Contracts\Validation\Rule;
use Modules\KiosDealer\Entities\SubDealer;

class SubDealerLatitudeRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($sub_dealer_id, $latitude)
    {
        $this->sub_dealer_id = $sub_dealer_id;
        $this->latitude = $latitude;
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
        if (!$sub_dealer->latitude && empty($this->latitude)) {
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
        return 'you need to send latitude, this sub dealer doesn\'t have latitude at all';
    }
}
