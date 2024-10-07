<?php

namespace Modules\Invoice\Rules;

use Illuminate\Contracts\Validation\Rule;
use Modules\Invoice\Entities\Invoice;

class UniqueInvoiceRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($invoice_id = null)
    {
        $this->invoice_id = $invoice_id;
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
        $invoice = Invoice::query()
            ->where("invoice", $value)
            ->when($this->invoice_id, function ($QQQ) {
                return $QQQ->where("id", "!=", $this->invoice_id["invoice"]);
            })
            ->first();

        if ($invoice) {
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
        return 'invoice number must unique, this invoice was used';
    }
}
