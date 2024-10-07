<?php

namespace Modules\ReceivingGood\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ReceivingGoodDetailProductRule implements Rule
{
    protected $messages;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    protected $request;

    public function __construct($request)
    {
        // $this->receiving_id = $receiving_id;
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
            foreach ($this->request->resources as $resource) {
                
                /**
                 * can not filll bith product_id and promotio_good_id, only one accepted
                 */
                if (isset($resource["product_id"]) && isset($resource["promotion_good_id"])) {
                    if ($resource["product_id"] && $resource["promotion_good_id"]) {
                        $this->messages = "can not fill both product_id and promotion_good_id, only one can accepted";
                        return false;
                    }
                }

                /* delivery order type check, promotion goor or not */
                $delivery_order = DB::table('delivery_orders as dor')
                    ->join("receiving_goods as rg", "rg.delivery_order_id", "dor.id")
                    ->whereNull("dor.deleted_at")
                    ->whereNull("rg.deleted_at")
                    ->where("dor.status", "send")
                    ->where("rg.id", $resource["receiving_good_id"])
                    ->first();

                /* delivery order check */
                if (!$delivery_order) {
                    $this->messages = "delivery order not found";
                    return false;
                }

                /* direct order dispatch */
                if (isset($resource["product_id"])) {

                    if ($delivery_order->is_promotion) {
                        $this->messages = "delivery order is for promotion good, can not receive as direct sales";
                        return false;
                    }

                    /**
                     * direct order dispatch check
                     * no need to check receiving ggod status
                     */
                    $dispatch_order_detail = DB::table('dispatch_order_detail as dod')
                        ->join("discpatch_order as dis", "dis.id", "dod.id_dispatch_order")
                        ->join("delivery_orders as dor", "dor.dispatch_order_id", "dis.id")
                        ->join("receiving_goods as rg", "rg.delivery_order_id", "dor.id")
                        ->whereNull("dor.deleted_at")
                        ->whereNull("rg.deleted_at")
                        ->where("dor.status", "send")
                        ->where("dod.id_product", $resource["product_id"])
                        ->where("dis.is_active", true)
                        ->where("rg.id", $resource["receiving_good_id"])
                        ->select("dod.*")
                        ->first();

                    if (!$dispatch_order_detail) {
                        $this->messages = "can not create receiving good detail, product_id is not in dispatch order";
                        return false;
                    }

                    if (isset($resource["quantity"])) {
                        if ($resource["quantity"] > $dispatch_order_detail->quantity_unit) {
                            $this->messages = "can not create receiving good detail, max quantity is " . $dispatch_order_detail->quantity_unit . " according dispatch";
                            return false;
                        }
                    }
                    return true;
                }

                /* promotion good dispatch */
                else {

                    /* promotion good dispatch */
                    if (!$delivery_order->is_promotion) {
                        $this->messages = "delivery order is for direct sales, can not receive as promotion good";
                        return false;
                    }
                    return true;
                }
            }

        } else {

            /**
             * can not filll bith product_id and promotio_good_id, only one accepted
             */
            if ($this->request->product_id && $this->request->promotion_good_id) {
                $this->messages = "can not fill both product_id and promotion_good_id, only one can accepted";
                return false;
            }

            /* delivery order type check, promotion goor or not */
            $delivery_order = DB::table('delivery_orders as dor')
                ->join("receiving_goods as rg", "rg.delivery_order_id", "dor.id")
                ->whereNull("dor.deleted_at")
                ->whereNull("rg.deleted_at")
                ->where("dor.status", "send")
                ->where("rg.id", $this->request->receiving_good_id)
                ->first();

            /* delivery order check */
            if (!$delivery_order) {
                $this->messages = "delivery order not found";
                return false;
            }

            if ($this->request->has("product_id")) {

                if ($delivery_order->is_promotion) {
                    $this->messages = "delivery order is for promotion good, can not receive as direct sales";
                    return false;
                }

                /**
                 * direct order dispatch check
                 * no need to check receiving ggod status
                 */
                $dispatch_order_detail = DB::table('dispatch_order_detail as dod')
                    ->join("discpatch_order as dis", "dis.id", "dod.id_dispatch_order")
                    ->join("delivery_orders as dor", "dor.dispatch_order_id", "dis.id")
                    ->join("receiving_goods as rg", "rg.delivery_order_id", "dor.id")
                    ->whereNull("dor.deleted_at")
                    ->whereNull("rg.deleted_at")
                    ->where("dor.status", "send")
                    ->where("dod.id_product", $this->request->product_id)
                    ->where("dis.is_active", true)
                    ->where("rg.id", $this->request->receiving_good_id)
                    ->select("dod.*")
                    ->first();

                if (!$dispatch_order_detail) {
                    $this->messages = "can not create receiving good detail, product_id is not in dispatch order";
                    return false;
                }

                if ($this->request->quantity > $dispatch_order_detail->quantity_unit) {
                    $this->messages = "can not create receiving good detail, max quantity is " . $dispatch_order_detail->quantity_unit . " according dispatch";
                    return false;
                }

                return true;
            }

            /* promotion good dispatch */
            else {

                if (!$delivery_order->is_promotion) {
                    $this->messages = "delivery order is for direct sales, can not receive as promotion good";
                    return false;
                }
                return true;
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
        return $this->messages;
    }
}
