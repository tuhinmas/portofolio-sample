<?php

namespace Modules\KiosDealer\Rules;

use Illuminate\Contracts\Validation\Rule;
use Modules\KiosDealer\Entities\Store;
use Modules\KiosDealer\Entities\StoreTemp;

class StoreLatitudeValidationRule implements Rule
{
    protected $message = null;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($store_temp_id, $latitude, $longitude)
    {
        $this->store_temp_id = $store_temp_id;
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
        $store_temp = StoreTemp::findOrFail($this->store_temp_id["store_temp"]);

        /* existing store update submission */
        if (($store_temp->latitude && $store_temp->longitude)) {
            return true;
        } elseif ($store_temp->store_id) {
            $store = Store::findOrFail($store_temp->store_id);
            if ($store->latitude && $store->longitude) {
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
        return "update store submission, latitude and longitude must set in store update submission";
    }
}
