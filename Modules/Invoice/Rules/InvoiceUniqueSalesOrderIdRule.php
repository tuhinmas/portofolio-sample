<?php

namespace Modules\Invoice\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class InvoiceUniqueSalesOrderIdRule implements Rule
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
        $invoice = DB::table('invoices')
            ->whereNull("deleted_at")
            ->where('sales_order_id', $value)
            // ->sharedLock()
            ->get();

        if ($invoice->count() > 0) {
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
        return 'This order was confirm, can not confirm again';
    }
}
