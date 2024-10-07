<?php

namespace Modules\ReceivingGood\Http\Requests;

use Orion\Http\Requests\Request;

class ReceivingGoodDetailIndirectSaleRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules(): array
    {
        return [
            "receiving_good_id" => "required",
            "product_id" => "required",
            "status" => "required",
            "note" => "required",
            "quantity" => "required",
            "quantity_package" => "required",
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function updateRules(): array
    {
        return [
            "receiving_good_id" => "string|max:255",
            "product_id" => "required_with:receiving_good_id",
            "status" => "required",
            "note" => "required",
            "quantity" => "required",
            "quantity_package" => "required",
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
