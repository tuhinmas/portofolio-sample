<?php

namespace Modules\DataAcuan\Rules\Fee;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class StatusFeeHistoryDateStartRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($history_id = null)
    {
        $this->history_id = $history_id;
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
        $status_fee = DB::table('status_fee_histories')
            ->whereNull("deleted_at")
            ->when($this->history_id, function ($QQQ) {
                return $QQQ->where("id", "!=", $this->history_id["fee_handover_status_history"]);
            })
            ->whereDate("date_start", Carbon::parse($value)->format("Y-m-d"))
            ->first();

        if ($status_fee) {
            return false;
        }

        return true;}

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'date_start for this date has already set';
    }
}
