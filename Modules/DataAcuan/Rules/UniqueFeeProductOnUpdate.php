<?php

namespace Modules\DataAcuan\Rules;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\Validation\Rule;

class UniqueFeeProductOnUpdate implements Rule
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
        /* get parameter of route */
        $product_group_id = Route::current()->parameters();

        if ($this->type == "1") {
            $fee_product_reguler = DB::table('fee_products')
                ->where("year", $this->year)
                ->where("type", "1")
                ->where("quartal", $this->quartal)
                ->where("product_id", $this->product_id)
                ->where("id", "!=", $product_group_id["fee"])
                ->whereNull("deleted_at")
                ->first();

            if ($fee_product_reguler) {
                $this->message = "Oww snap, you can't make two same regular product in the same year, please choose another year or another product, update";
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
                ->where("id", "!=", $product_group_id["fee"])
                ->whereNull("deleted_at")
                ->first();

            if ($fee_product_target) {
                $this->message = 'Hmmmmmb, you can\'t make two fee target product in this year, with same quantity, there was exist, choose another one';
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
