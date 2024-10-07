<?php

namespace Modules\PickupOrder\Rules;

use Illuminate\Contracts\Validation\Rule;
use Modules\PickupOrder\Entities\PickupOrderDispatch;

class PickupOrderV2DispatchRule implements Rule
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
        $pickupOrderDispatch = PickupOrderDispatch::whereIn("dispatch_id", $value)
            ->where(function($query) {
                $query->whereDoesntHave('pickupOrder')
                    ->orWhereHas('pickupOrder', function($q) {
                        $q->where('status', '!=', 'canceled');
                    });
            })
            ->first();

        if ($pickupOrderDispatch) {
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
        return 'ada dispatch yang sudah punya pickup order';
    }
}
