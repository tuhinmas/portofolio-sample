<?php

namespace Modules\DataAcuan\Rules\product;

use Illuminate\Contracts\Validation\Rule;
use Modules\DataAcuan\Entities\Price;

class productPriceAgencyLevelRule implements Rule
{
    protected $product_id;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($request, $method = null)
    {
        $this->request = $request;
        $this->method = $method;
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
        if ($this->request->has("resources") && $this->method == "POST") {
            $this->product_id = $this->request->resources[0]["product_id"];
        } elseif ($this->request->has("resources") && $this->method == "PATCH") {
            $resource = collect($this->request->resources)->filter(function($resource, $id) use($value){
                return $resource["agency_level_id"] == $value;
            });
            dd($resource->keys());
            $this->product_id = $this->request->resources[0]["product_id"];
        } else {
            $this->product_id = $this->request->product_id;
        }

        $price = Price::query()
            ->where("agency_level_id", $value)
            ->where("product_id", $this->product_id)
            ->first();

        if ($price) {
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
        return 'product price for this agency level was set, can not set again';
    }
}
