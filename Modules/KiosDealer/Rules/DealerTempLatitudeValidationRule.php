<?php

namespace Modules\KiosDealer\Rules;

use Modules\KiosDealer\Entities\Dealer;
use Illuminate\Contracts\Validation\Rule;
use Modules\KiosDealer\Entities\DealerTemp;

class DealerTempLatitudeValidationRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($dealer_temp_id, $latitude, $longitude)
    {
        $this->dealer_temp_id = $dealer_temp_id;
        $this->longitude = $longitude;
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
        $dealer_temp = DealerTemp::findOrFail($this->dealer_temp_id["dealer_temp"]);

        /* existing store update submission */
        if (($dealer_temp->latitude && $dealer_temp->longitude)) {
            return true;
        } elseif ($dealer_temp->dealer_id) {
            $dealer = Dealer::findOrFail($dealer_temp->dealer_id);
            if ($dealer->latitude && $dealer->longitude) {
                return true;
            } elseif ((empty($this->latitude) || empty($this->longitude))) {
                return false;
            }
        } else if ((empty($this->latitude) || empty($this->longitude))) {
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
        return 'The validation error message.';
    }
}
