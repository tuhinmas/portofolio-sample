<?php

namespace Modules\SalesOrder\Rules;

use Illuminate\Support\Facades\DB;
use Modules\Invoice\Entities\Invoice;
use Illuminate\Contracts\Validation\Rule;

class DirectSalesOnCancellationRule implements Rule
{
    protected $messages;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($sales_order_id)
    {
        $this->sales_order_id = $sales_order_id;
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
        if ($this->sales_order_id && $value = "canceled") {

            /**
             * new rule accroding 2024-01-10
             * direct sales can not be canceled if there
             * 1. active dispatch order
             * 2. invoice proforma
             */
            $invoice = DB::table('invoices')
                ->whereNull("deleted_at")
                ->where("sales_order_id", $this->sales_order_id["sales_order"])
                ->first();

            if ($invoice) {
                $is_cancelable = DB::table('discpatch_order as dpo')
                    ->whereNull("dpo.deleted_at")
                    ->where("invoice_id", $invoice->id)
                    ->where("is_active", true)
                    ->count() == 0;

                if (!$is_cancelable) {
                    return false;
                }

                $invoice_proforma = DB::table('invoice_proformas')
                    ->whereNull("deleted_at")
                    ->where("invoice_id", $invoice->id)
                    ->first();

                if ($invoice_proforma) {
                    return false;
                }
            }
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
        return "Can not cancel direct sales that has invoice proforma or already sent or has receving good";
    }
}
