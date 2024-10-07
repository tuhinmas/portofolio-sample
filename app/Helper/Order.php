<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Invoice\Entities\Invoice;
use Modules\SalesOrder\Entities\SalesOrder;

if (!function_exists("confirmation_time")) {
    function confirmation_time($sales_order)
    {
        if ($sales_order instanceof Invoice) {
            if (in_array($sales_order->salesOrder->status, ["confirmed", "pending", "returned"])) {
                return $sales_order->created_at;
            } else if ($sales_order->salesOrder->status == "canceled") {
                return $sales_order->created_at;
            }
        } else if (in_array($sales_order->status, ["confirmed", "pending", "returned"])) {
            if ($sales_order->type == "2") {
                return $sales_order->date ? Carbon::parse($sales_order->date)->endOfDay() : $sales_order->updated_at;
            } else {
                if ($sales_order->invoice) {
                    return $sales_order->invoice->created_at;
                } else {
                    return $sales_order->updated_at;
                }
            }
        } elseif ($sales_order->type == "2" && $sales_order->date) {
            return Carbon::parse($sales_order->date)->endOfDay();
        }

        return $sales_order->created_at;
    }
}

if (!function_exists("maximum_settle_days")) {

    function maximum_settle_days($year)
    {

        /* maximum settle days */
        $maximum_settle_days = DB::table('maximum_settle_days')
            ->whereNull("deleted_at")
            ->where("max_settle_for", "fee point marketing")
            ->where("year", $year)
            ->first();

        return $maximum_settle_days ? $maximum_settle_days->days : 60;
    }
}

if (!function_exists("is_return_order_exist")) {

    function is_return_order_exist($store_id, $personel_id, $year, $quarter)
    {
        $sales_order = SalesOrder::query()
            ->where("store_id", $store_id)
            ->where("personel_id", $personel_id)
            ->where("status", "returned")
            ->quartalOrder($year, $quarter)
            ->first();

        if ($sales_order) {
            return true;
        }

        return false;
    }
}

/**
 * check order if store has return or affected from return another order
 */
if (!function_exists("is_affected_from_return")) {

    function is_affected_from_return($sales_order)
    {
        if (!empty($sales_order->afftected_by_return)) {
            return true;
        } else {
            if (confirmation_time($sales_order)) {
                if (is_return_order_exist($sales_order->store_id, $sales_order->personel_id, confirmation_time($sales_order)->format("Y"), confirmation_time($sales_order)->quarter)) {
                    return true;
                }
                return false;
            }
        }

        return false;
    }
}

if (!function_exists("considered_order_status_for_recap")) {

    function considered_order_status_for_recap()
    {
        return ["confirmed", "pending", "returned"];
    }
}

if (!function_exists("considered_order_status_for_fee_active")) {

    function considered_order_status_for_fee_active()
    {
        return ["confirmed", "pending"];
    }
}

if (!function_exists("distributor_submited_sales")) {

    function distributor_submited_sales(): array
    {
        return ["submited"];
    }
}

if (!function_exists("is_considered_order_as_active_marketing_point")) {
    function is_considered_order_as_active_marketing_point($sales_order, $maximum_settle_days): bool
    {
        if ($sales_order->personel_id && !is_affected_from_return($sales_order)) {

            /**
             * order will be condidered active if settle in the same year with confirmation
             * date or in the different year but less then 60 days (according
             * maximum settle days data reference)
             */
            if ($sales_order->type == 1 && $sales_order->invoice->payment_status == "settle") {

                $is_point_counted_as_active = false;

                /**
                 * sales_order will be condidered active if settle in the same year with confirmation
                 * date or in the different year but less then 60 days (according
                 * data reference)
                 */
                if ($sales_order->invoice->last_payment == "-") {
                    $is_point_counted_as_active = true;
                } else if (Carbon::parse($sales_order->invoice->last_payment)->year == confirmation_time($sales_order)->year) {
                    $is_point_counted_as_active = true;
                } elseif (Carbon::parse($sales_order->invoice->last_payment)->year != confirmation_time($sales_order)->year) {
                    if (confirmation_time($sales_order)->diffInDays($sales_order->invoice->last_payment, false) <= ($maximum_settle_days ? $maximum_settle_days : 60)) {
                        $is_point_counted_as_active = true;
                    }
                }

                return $is_point_counted_as_active;
            }

            /**
             * indirect sale active point marketing date is according nota date
             * and always considered as active point
             */
            else if ($sales_order->type == "2") {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists("considered_orders")) {
    function considered_orders(): array
    {
        return ["confirmed", "pending", "returned"];
    }
}

if (!function_exists("considere_indirect_column")) {
    function considere_indirect_column() : string
    {
        return "date";
    }
}

if (!function_exists("considere_direct_column")) {
    function considere_direct_column() : string
    {
        return "created_at";
    }
}
