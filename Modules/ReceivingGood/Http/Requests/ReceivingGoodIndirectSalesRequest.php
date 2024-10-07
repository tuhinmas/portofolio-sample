<?php

namespace Modules\ReceivingGood\Http\Requests;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReceivingGoodIndirectSalesRequest extends Request
{
    use ResponseHandler;
    
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules():array
    {
        return [
            "sales_order_id" => "required|max:255",
            "status" => 'integer|max:9',
            "date_received" => "required|date",
            "receiving_type" => "required|integer|max:9",
            "shipping_number" => "required_if:receiving_type,2|max:255",
            "note" => "max:999999"
        ];
    }
    
    public function updateRules():array
    {
        return [
            "sales_order_id" => "max:255",
            "date_received" => "date",
            "receiving_type" => "integer|max:9",
            "shipping_number" => "required_if:receiving_type,2|max:255",
            "note" => "max:999999"
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

    protected function failedValidation(Validator $validation){
        $errors = $validation->errors();
        $response = $this->response("04", "invalid data send", $errors, 422);
        throw new HttpResponseException($response);
    }
}
