<?php

namespace Modules\DataAcuan\Rules\Fee;

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Validation\Rule;

class StatusFeeHistoryRule implements Rule
{
    protected $messages;

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
        $fee_position = DB::table('status_fee')
            ->whereNull("deleted_at")
            ->count();

        $is_valid = true;
        $count = collect($value)
            ->each(function ($fee_position) use (&$is_valid) {
                $is_valid = collect($fee_position)
                    ->has([
                        "name",
                        "percentage",
                        "maximum_settle_payment",
                        "minimum_days_marketing_join",
                    ]);

                if (!$is_valid) {
                    return false;
                }
            })
            ->unique("name")
            ->count();

        if ($fee_position != $count) {
            $this->messages = "status fee handover did not match with data references";
            return false;
        }
        if (!$is_valid) {
            $this->messages = "each status fee must contain these attribute (name, percentage, maximum_settle_payment, minimum_days_marketing_join)";
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
