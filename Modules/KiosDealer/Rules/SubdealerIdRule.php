<?php

namespace Modules\KiosDealer\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class SubdealerIdRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($dealer_temp = null, $sub_dealer_temp = null)
    {
        $this->dealer_temp = $dealer_temp;
        $this->sub_dealer_temp = $sub_dealer_temp;
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
        if (!$value) {
            if ($this->dealer_temp) {
                $dealer_temp = DB::table('dealer_temps')
                    ->where("id", $this->dealer_temp["dealer_temp"])
                    ->first();

                if ($dealer_temp) {
                    if ($dealer_temp->sub_dealer_id) {
                        return false;
                    }
                }
            }

            if ($this->sub_dealer_temp) {
                $sub_dealer_temp = DB::table('sub_dealer_temps')
                    ->where("id", $this->sub_dealer_temp["sub_dealer_temp"])
                    ->first();

                if ($sub_dealer_temp) {
                    if ($sub_dealer_temp->sub_dealer_id) {
                        return false;
                    }
                }
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
        return 'sub_dealer_id was fill and can not set to null';
    }
}
