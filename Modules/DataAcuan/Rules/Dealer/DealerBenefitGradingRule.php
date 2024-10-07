<?php

namespace Modules\DataAcuan\Rules\Dealer;

use Illuminate\Contracts\Validation\Rule;
use Modules\DataAcuan\Entities\DealerBenefit;

class DealerBenefitGradingRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($benefit_grade_id = null)
    {
        $this->benefit_grade_id = $benefit_grade_id;
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
        $dealer_benefit = DealerBenefit::query()
            ->where("grading_id", $value)
            ->when($this->benefit_grade_id, function ($QQQ) {
                return $QQQ->where("id", "!=", $this->benefit_grade_id["dealer_benefit"]);
            })
            ->first();
        
        if ($dealer_benefit) {
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
        return 'this benefit grading has been set, can not set with same grading';
    }
}
