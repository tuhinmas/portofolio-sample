<?php

namespace Modules\DistributionChannel\Rules;

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Validation\Rule;

class DeliveryOrderPromotionUniqueRule implements Rule
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

        $delivery_order = DB::table("delivery_orders")
            ->whereNull("deleted_at")
            ->where("status", "send")
            ->where("dispatch_promotion_id", $value)
            ->first();

        if ($delivery_order) {
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
        return 'Barang promosi ini sudah memiliki surat jalan';
    }
}
