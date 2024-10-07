<?php

namespace Modules\DistributionChannel\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Modules\DistributionChannel\Entities\DispatchOrder;

class DeliveryOrderRule implements Rule
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
        /* lock all proccess in perform  store for this order */
        $dispatch = DispatchOrder::query()
            ->with("invoice.salesOrder")
            ->findOrFail($value);

        // jika dispatch order sudah mempunyai deliveri order, maka gagal.
        $delivery = DB::table('delivery_orders')
            ->whereNull("deleted_at")
            ->where("dispatch_order_id", $dispatch->id)
            ->where("status", "send")
            ->first();

        if ($delivery) {
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
        return 'delivery order has been there';
    }
}
