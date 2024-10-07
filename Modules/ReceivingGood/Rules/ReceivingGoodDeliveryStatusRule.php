<?php

namespace Modules\ReceivingGood\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Modules\DistributionChannel\Entities\DeliveryOrder;

class ReceivingGoodDeliveryStatusRule implements Rule
{
    protected $messages;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($request, $receiving_good_id = null)
    {
        $this->request = $request;
        $this->receiving_good_id = $receiving_good_id;
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
        if ($value != "2") {
            return true;
        }

        /* can not set receiving to deliverd if already exist on update*/
        if ($this->receiving_good_id) {
            $receiving_good = DB::table('receiving_goods')
                ->whereNull("deleted_at")
                ->where("id", $this->receiving_good_id["receiving_good"])
                ->first();

            $receiving_good_by_delivery = DB::table('receiving_goods as rg')
                ->whereNull("rg.deleted_at")
                ->where("delivery_order_id", $receiving_good?->delivery_order_id)
                ->where("rg.delivery_status", "2")
                ->where("id", "!=", $this->receiving_good_id["receiving_good"])
                ->first();

            if ($receiving_good_by_delivery) {
                $this->messages = "delivery has receiving good, can not set to delivered";
                return false;
            }
            
            $deliveryOrder = DeliveryOrder::find($receiving_good?->delivery_order_id);

            if ($deliveryOrder->is_promotion == 1) {
                $dispatch_promotion_detail = DB::table('dispatch_promotion_details as dpd')
                    ->join("dispatch_promotions as dp", "dp.id", "dpd.dispatch_promotion_id")
                    ->join("delivery_orders as dor", "dor.dispatch_promotion_id", "dp.id")
                    ->whereNull("dpd.deleted_at")
                    ->whereNull("dp.deleted_at")
                    ->whereNull("dor.deleted_at")
                    ->where("dor.id", $receiving_good?->delivery_order_id)
                    ->sum("dpd.quantity_unit");
   
                $receiving_good_detail = DB::table('receiving_good_details as rgd')
                    ->wherenull("rgd.deleted_at")
                    ->where("rgd.receiving_good_id", $this->receiving_good_id["receiving_good"])
                    ->sum("rgd.quantity");
    
                if ($receiving_good_detail > $dispatch_promotion_detail) {
                    $this->messages = "can not set receiving good to deliverd, max quantity is " . $dispatch_promotion_detail . " according dispatch";
                    return false;
                }
                return true;
            }else{
                $dispatch_order_detail = DB::table('dispatch_order_detail as dod')
                    ->join("discpatch_order as dis", "dis.id", "dod.id_dispatch_order")
                    ->join("delivery_orders as dor", "dor.dispatch_order_id", "dis.id")
                    ->whereNull("dod.deleted_at")
                    ->whereNull("dis.deleted_at")
                    ->whereNull("dor.deleted_at")
                    ->where("dor.id", $receiving_good?->delivery_order_id)
                    ->sum("dod.quantity_unit");

                $receiving_good_detail = DB::table('receiving_good_details as rgd')
                    ->wherenull("rgd.deleted_at")
                    ->where("rgd.receiving_good_id", $this->receiving_good_id["receiving_good"])
                    ->sum("rgd.quantity");

                if ($receiving_good_detail > $dispatch_order_detail) {
                    $this->messages = "can not set receiving good to deliverd, max quantity is " . $dispatch_order_detail . " according dispatch";
                    return false;
                }
                return true;
            }

        }

        /* can not set receiving to delivered if already exist on store */
        elseif ($this->request->has("delivery_order_id")) {
            $receiving_good_by_delivery = DB::table('receiving_goods as rg')
                ->whereNull("rg.deleted_at")
                ->where("delivery_order_id", $this->request->delivery_order_id)
                ->where("rg.delivery_status", "2")
                ->first();

            if ($receiving_good_by_delivery) {
                $this->messages = "delivery has receiving good, can not set to delivered";
                return false;
            }

            return true;
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
        return $this->messages;
    }
}
