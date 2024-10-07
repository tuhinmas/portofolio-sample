<?php

namespace Modules\Invoice\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class CreditMemoDestinationRule implements Rule
{
    protected $messages;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($request)
    {
        $this->request = $request;
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
        $sales_orders = DB::table('sales_orders as s')
            ->join("invoices as i", "i.sales_order_id", "s.id")
            ->whereNull("i.deleted_at")
            ->whereNull("s.deleted_at")
            ->whereIn("i.id", [$value, $this->request->memo["origin_id"]])
            ->select("s.*", "i.id as proforma_id", "i.payment_status")
            ->get();

        $is_passed = true;
        switch (true) {
            case $sales_orders->pluck("store_id")->unique()->count() > 1:
                $this->messages = "proforma tujuan harus di toko yang sama dengan proforma asal";
                $is_passed = false;
                break;

            case $sales_orders->filter(fn($order) => $order->proforma_id == $value)->first()?->payment_status == "settle" && $value != $this->request->memo["origin_id"]:
                $this->messages = "proforma yang sudah lunas tidak bisa dijadikan tujuan kecuali sama dengan asal";
                $is_passed = false;
                break;

            default:
                break;
        };

        return $is_passed;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->messages;
    }
}
