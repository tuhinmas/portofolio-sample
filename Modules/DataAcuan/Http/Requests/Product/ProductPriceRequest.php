<?php

namespace Modules\DataAcuan\Http\Requests\product;

use Orion\Http\Requests\Request;
use Modules\DataAcuan\Rules\product\productPriceValidFromRule;
use Modules\DataAcuan\Rules\product\productPriceAgencyLevelRule;

class ProductPriceRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules(): array
    {
        $method = $this->method();
        return [
            "product_id" => [
                "required",
            ],
            "agency_level_id" => [
                "required",
                new productPriceAgencyLevelRule($this, $method)
            ],
            "het" => [
                "required",
                "min:1",
                "max:999999",
            ],
            "price" => [
                "required",
                "min:1",
                "max:999999",
            ],
            "minimum_order" => [
                "required",
                "min:1",
                "max:999999",
            ],
            "valid_from" => [
                "required",
                "date",
            ],
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function updateRules(): array
    {
        $method = $this->method();
        return [
            "product_id" => [
                "required",
            ],
            "agency_level_id" => [
                "required",
                new productPriceAgencyLevelRule($this, $method)
            ],
            "het" => [
                "min:1",
                "max:999999",
            ],
            "price" => [
                "min:1",
                "max:999999",
            ],
            "minimum_order" => [
                "min:1",
                "max:999999",
            ],
            "valid_from" => [
                "required",
                "date",
                new productPriceValidFromRule($this)
            ],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
