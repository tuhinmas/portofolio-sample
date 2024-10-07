<?php

namespace Modules\Invoice\Http\Requests;

use Orion\Http\Requests\Request;
use Modules\Invoice\Rules\CreditMemoOriginRule;
use Modules\Invoice\Rules\CreditMemoDestinationRule;

class CreditMemoRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function commonRules(): array
    {
        return [
            "memo" => "required|max:255",
            "memo.personel_id" => "required|max:40",
            "memo.dealer_id" => "required|max:40",
            "memo.origin_id" => [
                "required",
                "max:40",
                new CreditMemoOriginRule($this),
            ],
            "memo.destination_id" => [
                "required",
                "max:40",
                new CreditMemoDestinationRule($this),
            ],
            "memo.date" => "required|date",
            "memo.tax_invoice" => "string|max:255|nullable",
            "memo.reason" => "required|string|max:255|min:3",
            "products" => "required|array",
            "products.*.product_id" => "required|string|max:255",
            "products.*.quantity_return" => "required|integer|max:999999999",
            "products.*.unit_price_return" => "required|numeric|max:999999999999",
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
