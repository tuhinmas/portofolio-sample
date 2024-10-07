<?php

namespace Modules\PickupOrder\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class PickupOrderDetailIsLoadedRule implements Rule
{
    protected $message;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($pickup_order_detail_id, $request = null)
    {
        $this->pickup_order_detail_id = $pickup_order_detail_id;
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
        /* is_loaded: true */
        if ($value) {

            /* attachment rule */
            $pickup_order_detail_id = DB::table('pickup_order_detail_files as podf')
                ->where("pickup_order_detail_id", $this->pickup_order_detail_id["pickup_order_detail"])
                ->join("pickup_order_details as pod", "pod.id", "podf.pickup_order_detail_id")
                ->whereNull("podf.deleted_at")
                ->whereNull("pod.deleted_at")
                ->where("pod.pickup_type", "load")
                ->where("podf.type", "load")
                ->select("podf.id as file_id", "pod.*")
                ->get();

            /* no attachment found */
            if ($pickup_order_detail_id->count() <= 0) {
                $this->message = "can not checked pickup, attachment needed";
                return false;
            }

            /* qty actual */
            else {
                if ($this->request) {

                    if ($this->request->has("quantity_actual_load")) {
                        if ($this->request->quantity_actual_load < $pickup_order_detail_id->first()->quantity_unit_load) {
                            $this->message = "actual quantity load can not higher then quantity load";
                            return false;
                        }
                    }

                } elseif ($pickup_order_detail_id->first()->quantity_actual_load < $pickup_order_detail_id->first()->quantity_unit_load) {

                    $this->message = "actual quantity load can not higher then quantity load";
                    return false;

                }
            }
        } else {

            /* attachment rule */
            $pickup_order_detail_id = DB::table('pickup_order_detail_files as podf')
                ->where("pickup_order_detail_id", $this->pickup_order_detail_id["pickup_order_detail"])
                ->join("pickup_order_details as pod", "pod.id", "podf.pickup_order_detail_id")
                ->whereNull("podf.deleted_at")
                ->whereNull("pod.deleted_at")
                ->where("pod.pickup_type", "unload")
                ->where("podf.type", "unload")
                ->select("podf.id as file_id", "pod.*")
                ->get();

            /* no attachment found */
            if ($pickup_order_detail_id->count() <= 0) {
                $this->message = "can not checked pickup, attachment needed";
                return false;
            }

             /* qty actual */
             else {
                if ($this->request) {
                    if ($this->request->has("quantity_actual_load")) {
                        if ($this->request->quantity_actual_load > 0) {
                            $this->message = "actual quantity load must 0";
                            return false;
                        }
                    }
                } elseif ($pickup_order_detail_id->first()->quantity_actual_load > 0) {
                    $this->message = "actual quantity load must 0";
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
        return $this->message;
    }
}
