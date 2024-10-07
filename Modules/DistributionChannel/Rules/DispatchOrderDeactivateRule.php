<?php

namespace Modules\DistributionChannel\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Modules\DistributionChannel\Entities\DispatchOrder;

class DispatchOrderDeactivateRule implements Rule
{
    protected $dispatch_order_id, $messages, $is_passed = true;

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
        if ($value) {
            return true;
        }

        $dispatch_order = DispatchOrder::findOrFail($this->dispatch_order_id["dispatch_order"]);
        switch (true) {
            case self::hasPickupOrder($dispatch_order):
                $this->messages = "Dispatch order tidak bisa dibatalkan karena sudah dipickup";
                $this->is_passed = false;
                break;

            case self::hasDeliveryOrder($dispatch_order):
                $this->messages = "Dispatch order tidak bisa di batalkan karena sudah memiliki surat jalan aktif";
                $this->is_passed = false;
                break;

            case self::hasReceivingGood($dispatch_order):
                $this->messages = "Dispatch order tidak bisa di batalkan karena sudah diterima'";
                $this->is_passed = false;
                break;

            default:
                break;
        }

        return $this->is_passed;
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

    /**
     * is dispatch order has receiving good
     * even with invalid delivery order
     *
     * @param DispatchOrder $dispatch_order
     * @return boolean
     */
    public static function hasReceivingGood(DispatchOrder $dispatch_order): bool
    {
        $receiving_good = DB::table('delivery_orders as dor')
            ->join("receiving_goods as rg", "rg.delivery_order_id", "dor.id")
            ->whereNull("dor.deleted_at")
            ->whereNull("rg.deleted_at")
            ->where("dispatch_order_id", $dispatch_order->id)
            ->where("rg.delivery_status", "2")
            ->orderBy("dor.date_delivery", "desc")
            ->orderBy("dor.updated_at", "desc")
            ->first();

        return $receiving_good ? true : false;
    }

    /**
     * is dispatch order has receiving good
     * even with invalid delivery order
     *
     * @param DispatchOrder $dispatch_order
     * @return boolean
     */
    public static function hasPickupOrder(DispatchOrder $dispatch_order): bool
    {
        $has_pickup_order = DB::table('pickup_orders as po')
            ->join("pickup_order_dispatches as pod", "po.id", "pod.pickup_order_id")
            ->whereIn("po.status", ["loaded", "planned", "revised"])
            ->where("pod.dispatch_id", $dispatch_order->id)
            ->whereNull("po.deleted_at")
            ->whereNull("pod.deleted_at")
            ->select("po.*")
            ->first();

        return $has_pickup_order ? true : false;
    }

    /**
     * is dispatch order has delivery order
     *
     * @param DispatchOrder $dispatch_order
     * @return boolean
     */
    public static function hasDeliveryOrder(DispatchOrder $dispatch_order): bool
    {
        $last_delivery_order = DB::table('delivery_orders')
            ->where("dispatch_order_id", $dispatch_order->id)
            ->where("status", "send")
            ->whereNull("deleted_at")
            ->orderBy("date_delivery", "desc")
            ->orderBy("updated_at", "desc")
            ->first();

        return $last_delivery_order ? true : false;
    }
}
