<?php

namespace Modules\PickupOrder\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class PickupOrderDetailFileTypeRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($pickup_order_detail_id)
    {
        $this->pickup_order_detail_id = $pickup_order_detail_id;
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
        $pickup_order_detail = DB::table('pickup_order_details')
            ->whereNull("deleted_at")
            ->where("id", $this->pickup_order_detail_id)
            ->first();

        if ($pickup_order_detail) {
            return $pickup_order_detail->pickup_type == $value;
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
        return 'Jenis lampiran harus sesuai dengan jenis dispatchnya';
    }
}
