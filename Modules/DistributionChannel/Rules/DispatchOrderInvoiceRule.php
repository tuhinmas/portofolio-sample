<?php

namespace Modules\DistributionChannel\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class DispatchOrderInvoiceRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($dispatch_order_id = null)
    {
        $this->dispatch_order_id = $dispatch_order_id;
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
        if ($this->dispatch_order_id) {
            $invoice = DB::table('invoices as i')
                ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
                ->whereNull("i.deleted_at")
                ->whereNull("dis.deleted_at")
                ->where("dis.id", $this->dispatch_order_id["dispatch_order"])
                ->first();

            if (in_array($invoice?->delivery_status, [1, 3])) {
                return false;
            }
        }

        $invoice = DB::table('invoices')
            ->whereNull("deleted_at")
            ->where("id", $value)
            ->first();

        if (in_array($invoice?->delivery_status, [1, 3])) {
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
        return 'can not create/update dispatch order, this proforma was done or consider done';
    }
}
