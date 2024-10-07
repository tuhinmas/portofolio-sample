<?php

namespace Modules\Invoice\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class CreditMemoOriginRule implements Rule
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
        $origin = DB::table('sales_orders as s')
            ->join("invoices as i", "i.sales_order_id", "s.id")
            ->whereNull("i.deleted_at")
            ->whereNull("s.deleted_at")
            ->whereIn("i.id", [$value])
            ->select("s.*", "i.id as proforma_id", "i.payment_status", "i.delivery_status")
            ->first();

        $is_passed = true;
        switch (true) {
            case !$origin:
                $this->messages = "origin tidak ditemukan";
                $is_passed = false;
                break;

            case !in_array($origin->delivery_status, [1, 3]):
                $this->messages = "proforma asal belum diterima semua";
                $is_passed = false;
                break;

            case $origin->payment_status != "settle" && $value != $this->request->memo["destination_id"]:
                $this->messages = "proforma asal harus sudah lunas";
                $is_passed = false;
                break;

            case $origin->status == "returned" && $value != $this->request->memo["destination_id"]:
                $this->messages = "proforma asal sudah pernah return, tidak bisa untuk kredit memo proforma lain";
                $is_passed = false;
                break;

            default:
                break;
        }

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
