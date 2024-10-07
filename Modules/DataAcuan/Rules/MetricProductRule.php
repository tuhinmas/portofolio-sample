<?php

namespace Modules\DataAcuan\Rules;

use Illuminate\Contracts\Validation\Rule;
use Modules\DataAcuan\Entities\Product;

class MetricProductRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($product_id)
    {
        $this->product_id = $product_id;
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
        if (count($this->product_id) > 0) {
            $products = Product::query()
                ->whereIn("id", $this->product_id)
                ->get()
                ->pluck("metric_unit")
                ->unique();

            if(count($products) >= 2){
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
        return 'validation.metric_unit_different';
    }
}
