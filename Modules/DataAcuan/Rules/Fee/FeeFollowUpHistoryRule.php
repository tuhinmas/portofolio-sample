<?php

namespace Modules\DataAcuan\Rules\Fee;

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Validation\Rule;

class FeeFollowUpHistoryRule implements Rule
{
    private $messages;

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
        $fee_follow_up = DB::table('fee_follow_ups')
            ->whereNull("deleted_at")
            ->count();

        $is_valid = true;
        $count = collect($value)
            ->each(function ($fee_follow_up) use (&$is_valid) {
                $is_valid = collect($fee_follow_up)
                    ->has([
                        "fee",
                        "settle_days",
                        "follow_up_days",
                    ]);

                if (!$is_valid) {
                    return false;
                }
            })
            ->count();

        if ($fee_follow_up != $count) {
            $this->messages = "fee follow up did not match with data references";
            return false;
        }
        if (!$is_valid) {
            $this->messages = "each fee follow up must contain these attribute (fee, settle_days, follow_up_days)";
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
        return $this->messages;
    }
}
