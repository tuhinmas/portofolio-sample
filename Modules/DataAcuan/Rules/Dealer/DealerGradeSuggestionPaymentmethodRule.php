<?php

namespace Modules\DataAcuan\Rules\Dealer;

use Illuminate\Contracts\Validation\Rule;
use Modules\DataAcuan\Entities\PaymentMethod;

class DealerGradeSuggestionPaymentmethodRule implements Rule
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
    public function passes($attribute, $payment_methods)
    {
        $passed = true;
        $payment_method_reference = PaymentMethod::all()
            ->pluck("name")
            ->unique()
            ->map(fn($payment) => strtolower($payment))
            ->sort()
            ->toArray();

        foreach ($payment_methods as $payment_method) {
            if (!in_array($payment_method, $payment_method_reference)) {
                $passed = false;
                $this->messages = implode(", ", $payment_method_reference);
                break;
            }
        }

        return $passed;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'metode pembyaran hanya boleh terdiri dari '. $this->messages;
    }
}
