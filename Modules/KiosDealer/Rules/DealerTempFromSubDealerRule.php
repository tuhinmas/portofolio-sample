<?php

namespace Modules\KiosDealer\Rules;

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Validation\Rule;

class DealerTempFromSubDealerRule implements Rule
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
        if (!$value) {
            return true;
        }
        
        $sub_dealer_submission = DB::table('sub_dealer_temps')
            ->whereNull("deleted_at")
            ->whereNotIn("status", ["filed rejected", "change rejected"])
            ->where("sub_dealer_id", $value)
            ->first();

        if ($sub_dealer_submission) {
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
        return 'can not create dealer submission, sub dealer already has submission on change';
    }
}
