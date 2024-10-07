<?php

namespace Modules\DataAcuan\Rules\Fee;

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Validation\Rule;

class FeeFollowUpDaysRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($fee_follow_up_id = null)
    {
        $this->fee_follow_up_id = $fee_follow_up_id;
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
        $follow_up_days = DB::table('fee_follow_ups')
            ->whereNull("deleted_at")
            ->where("follow_up_days", $value)
            ->when($this->fee_follow_up_id, function ($QQQ) {
                return $QQQ->where("id", "!=", $this->fee_follow_up_id["fee_follow_up"]);
            })
            ->first();

        if ($follow_up_days) {
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
        return 'follow up days has been use, choose another one';
    }
}
