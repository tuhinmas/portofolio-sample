<?php

namespace Modules\Invoice\Http\Requests;

use App\Traits\ResponseHandler;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Orion\Http\Requests\Request;

class AdjustmentStockRequest extends Request
{
    use ResponseHandler;
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules(): array
    {
        return [
            "opname_date" => [
                "required_if:is_first_stock,0",
                "max:255"
            ],
            "current_stock" => "max:255",
            "product_id" => "required|max:255",
            "product_in_warehouse" => [
                "required_if:is_first_stock,0",
                "integer",
                "max:999999999999",
            ],
            "product_unreceived_by_distributor" => [
                "required_if:is_first_stock,0",
                "integer",
                "max:999999999999",
            ],
            "product_undelivered_by_distributor" => [
                "required_if:is_first_stock,0",
                "integer",
                "max:999999999999",
            ],
            "is_first_stock" => "required|boolean",
            "stock_price" => [
                "required_if:is_first_stock,1",
            ],
            "real_stock" => [
                "required_if:is_first_stock,1",
                "max:9999999999"
            ]
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
            "opname_date" => "max:255",
            "real_stock" => "max:255",
            "current_stock" => "max:255",
            "product_id" => "max:255",
            "product_in_warehouse" => "integer|max:999999999999",
            "product_unreceived_by_distributor" => "integer|max:999999999999",
            "product_undelivered_by_distributor" => "integer|max:999999999999",
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

    protected function failedValidation(Validator $validation)
    {
        $errors = $validation->errors();
        $response = $this->response('04', 'invalida data send', $errors);
        throw new HttpResponseException($response);
    }
}
