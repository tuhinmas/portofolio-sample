<?php

namespace Modules\KiosDealer\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Modules\KiosDealer\Entities\SubDealer;

class SubDealerCheckOriginOnSubmissionRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($sub_dealer_id = null)
    {
        $this->sub_dealer_id = $sub_dealer_id;
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
        if ($value) {
            $dealertemp = DB::table('dealer_temps')
                ->whereNull("deleted_at")
                ->where("sub_dealer_id", $value)
                ->whereNotIn("status", ["filed rejected", "change rejected"])
                ->first();

            $sub_dealer_temp = DB::table('sub_dealer_temps')
                ->whereNull("deleted_at")
                ->where("sub_dealer_id", $value)
                ->whereNotIn("status", ["filed rejected", "change rejected"])
                ->when($this->sub_dealer_id, function ($QQQ) {
                    return $QQQ->where("id", "!=", $this->sub_dealer_id["sub_dealer_temp"]);
                })
                ->first();

            $sub_dealer = SubDealer::find($value);

            if ($sub_dealer) {
                if ($sub_dealer->dealer_id) {
                    return false;
                }
            }

            if ($dealertemp || $sub_dealer_temp) {
                return false;
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
        return 'can not store, sub dealer is on transfer submission to dealer';
    }
}
