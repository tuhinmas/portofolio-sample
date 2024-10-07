<?php

namespace Modules\SalesOrder\Rules;

use Illuminate\Contracts\Validation\Rule;
use Modules\SalesOrder\Entities\SalesOrder;

class DirectSaleLatitudeRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($sales_order_id, $current_route, $latitude, $longitude)
    {
        $this->sales_order_id = $sales_order_id;
        $this->current_route = $current_route;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
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
        $sales_order = SalesOrder::findOrFail($this->sales_order_id["sales_order"]);
        // $sales_order->latitude ?: "-7.7876441770856095"; 
        // $sales_order->longitude ?: "110.37430905476309"; 
        if ($sales_order->type == "1" && !$sales_order->latitude && !$sales_order->longitude && in_array("PUT", $this->current_route) && empty($this->latitude) && empty($this->longitude)) {
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
        return 'latitude and longitude must set in direct sale';
    }
}
