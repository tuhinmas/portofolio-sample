<?php

namespace Modules\DistributionChannel\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Modules\DistributionChannel\Actions\GetQuantityLoadedByProductAction;
use Modules\DistributionChannel\Actions\GetQuantityOrderByProductAndDispatchAction;

class DispatchOrderActivateRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($dispatch_order_id)
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
        $dispatch_order = DB::table('discpatch_order')
            ->where("id", $this->dispatch_order_id["dispatch_order"])
            ->first();

        if ($dispatch_order?->is_active == false && $value = true) {
            $sales_order_detail = (new GetQuantityOrderByProductAndDispatchAction)($this->dispatch_order_id["dispatch_order"]);

            if (!$sales_order_detail?->invoice_id) {
                return false;
            }

            $qty_loaded = (new GetQuantityLoadedByProductAction)($sales_order_detail?->invoice_id);

            $dispatch_order_detail = DB::table('dispatch_order_detail as dod')
                ->join("discpatch_order as dis", "dod.id_dispatch_order", "=", "dis.id")
                ->leftJoin("delivery_orders as dor", function ($join) {
                    $join->on("dor.dispatch_order_id", "=", "dis.id")
                        ->where("dor.status", "=", "send")
                        ->whereNull("dor.deleted_at");
                })
                ->whereNull("dod.deleted_at")
                ->whereNull("dis.deleted_at")
                ->where("dis.id", $this->dispatch_order_id["dispatch_order"])
                ->select("dod.*", "dor.id as delivery_order_id")
                ->get()
                ->sum(function ($dispatch) {
                    if ($dispatch->delivery_order_id) {
                        return $dispatch->quantity_unit;
                    }
                    return $dispatch->planned_quantity_unit;
                });
            
            if ($qty_loaded + $dispatch_order_detail > $sales_order_detail?->quantity) {
                return false;
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
        return 'can not activate dispatch order, quantity loaded is higher than quantity order';
    }
}
