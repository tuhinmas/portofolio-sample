<?php

namespace Modules\KiosDealer\Rules;

use Illuminate\Contracts\Validation\Rule;
use Modules\KiosDealer\Entities\Store;

class StoreFixInUpdateLatitudeValidationRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($store_id, $latitude, $longitude)
    {
        $this->store_id = $store_id;
        $this->latitude = $latitude;
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
        $store = Store::findOrFail($this->store_id);
        if ((!$store->latitude || !$store->longitude) && (empty($this->latitude) || empty($this->longitude))) {
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
        return 'latitude and longitude is required if store doesn\'t have it before';
    }
}
