<?php

namespace Modules\DistributionChannel\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class DispatchOrderDetailProductRule implements Rule
{
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
        if ($this->request->has("resources")) {
            foreach ($this->request->resources as $resources) {

                /* qty order */
                $order_detail = DB::table('sales_order_details as sod')
                    ->join("sales_orders as s", "s.id", "sod.sales_order_id")
                    ->join("invoices as i", "i.sales_order_id", "s.id")
                    ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
                    ->where("sod.product_id", $resources["id_product"])
                    ->where("dis.id", $resources["id_dispatch_order"])
                    ->whereNull("sod.deleted_at")
                    ->whereNull("s.deleted_at")
                    ->whereNull("i.deleted_at")
                    ->whereNull("dis.deleted_at")
                    ->first();

                if (!$order_detail) {
                    return false;
                }
            }
            return true;
        } else {

            /* qty order */
            $order_detail = DB::table('sales_order_details as sod')
                ->join("sales_orders as s", "s.id", "sod.sales_order_id")
                ->join("invoices as i", "i.sales_order_id", "s.id")
                ->join("discpatch_order as dis", "dis.invoice_id", "i.id")
                ->where("sod.product_id", $this->request->id_product)
                ->where("dis.id", $this->request->id_dispatch_order)
                ->whereNull("sod.deleted_at")
                ->whereNull("s.deleted_at")
                ->whereNull("i.deleted_at")
                ->whereNull("dis.deleted_at")
                ->first();

            if (!$order_detail) {
                return false;
            }

            return true;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'product is not inside order, choose the right one';
    }
}
