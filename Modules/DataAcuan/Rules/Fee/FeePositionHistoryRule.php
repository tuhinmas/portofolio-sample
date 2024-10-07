<?php

namespace Modules\DataAcuan\Rules\Fee;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class FeePositionHistoryRule implements Rule
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
        $fee_position = DB::table('fee_positions')
            ->whereNull("deleted_at")
            ->count();

        $is_valid = true;
        $count = collect($value)
            ->each(function ($fee_position) use (&$is_valid) {
                $is_valid = collect($fee_position)
                    ->has([
                        "position_id",
                        "fee",
                        "follow_up",
                        "fee_cash",
                        "fee_cash_minimum_order",
                        "fee_sc_on_order",
                        "maximum_settle_days",
                        "settle_from",
                        "fee_as_marketing",
                        "is_applicator",
                        "is_mm",
                    ]);

                if (!$is_valid) {
                    return false;
                }
            })
            ->unique("position_id")
            ->count();

        if ($fee_position != $count) {
            $this->messages = "fee_position did not match with data references";
            return false;
        }
        if (!$is_valid) {
            $this->messages = "each position must contain these attribute (position_id, fee,follow_up, fee_cash, fee_cash_minimum_order, fee_sc_on_order, maximum_settle_days, settle_from, fee_as_marketing, is_applicator,is_mm)";
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
