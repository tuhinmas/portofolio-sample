<?php

namespace Modules\DataAcuan\Rules;

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Validation\Rule;

class UniqueFeeProductRule implements Rule
{
    public $message = null;
    
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($year, $product_id, $quantity, $fee, $type, $quartal)
    {
        $this->year = $year;
        $this->product_id = $product_id;
        $this->quantity = $quantity;
        $this->fee = $fee;
        $this->type = $type;
        $this->quartal = $quartal;
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
        if ($this->type == "1") {
            $fee_product_reguler = DB::table('fee_products')
                ->where("year", $this->year)
                ->where("quartal", $this->quartal)
                ->where("type", "1")
                ->where("product_id", $this->product_id)
                ->whereNull("deleted_at")
                ->first();

            if ($fee_product_reguler) {
                $this->message = "Oww snap, you can't make two same regular product in the same year and same quartal, please choose another year on quartal or another product";
                return false;
            }
            return true;
        } else {
            $fee_product_target = DB::table('fee_products')
                ->where("year", $this->year)
                ->where("type", "2")
                ->where("quartal", $this->quartal)
                ->where("product_id", $this->product_id)
                ->where("quantity", $this->quantity)
                ->whereNull("deleted_at")
                ->first();

            if ($fee_product_target) {
                $this->message = 'Hmmmmmb, you can\'t make two fee target product in this year, with same quantity, same fee on one quartal, there was exist, choose another one';
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
        return $this->message;
    }
}
