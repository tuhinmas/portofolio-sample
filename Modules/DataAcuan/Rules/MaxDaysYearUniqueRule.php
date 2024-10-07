<?php

namespace Modules\DataAcuan\Rules;

use Illuminate\Contracts\Validation\Rule;
use Modules\DataAcuan\Entities\MaxDaysReference;

class MaxDaysYearUniqueRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($maximum_days_for, $maximum_days_reference = null)
    {
        $this->maximum_days_reference = $maximum_days_reference;
        $this->maximum_days_for = $maximum_days_for;
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
        $maximum_days = MaxDaysReference::query()
            ->where("year", $value)
            ->where("maximum_days_for", $this->maximum_days_for)
            ->when($this->maximum_days_reference, function($QQQ){
                return $QQQ->where("id", "!=", $this->maximum_days_reference);
            })
            ->first();

        if ($maximum_days) {
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
        return 'Year must unique for every maximum days for';
    }
}
