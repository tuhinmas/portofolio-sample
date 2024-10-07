<?php

namespace Modules\Invoice\Http\Requests;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AdjustmentStockMarketingRequest extends Request
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
                "required",
                "date",
                "max:255",
            ],
            "current_stock" => "max:255",
            "product_id" => "required|max:255",
            "product_in_warehouse" => [
                "required",
                "integer",
                "max:999999999999",
            ],
            "product_unreceived_by_distributor" => [
                "required",
                "integer",
                "max:999999999999",
            ],
            "product_undelivered_by_distributor" => [
                "required",
                "integer",
                "max:999999999999",
            ],
            "contract_id" => [
                "required",
                "min:32",
                "max:36"
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
            "opname_date" => [
                "required",
                "date",
                "max:255",
            ],
            "current_stock" => "max:255",
            "product_id" => "required|max:255",
            "product_in_warehouse" => [
                "required",
                "integer",
                "max:999999999999",
            ],
            "product_unreceived_by_distributor" => [
                "required",
                "integer",
                "max:999999999999",
            ],
            "product_undelivered_by_distributor" => [
                "required",
                "integer",
                "max:999999999999",
            ],
            "contract_id" => [
                "min:32",
                "max:36"
            ]
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
