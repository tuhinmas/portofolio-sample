<?php

namespace Modules\SalesOrder\Http\Requests;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReceivingGoodDetailIndirectSalesRequest extends Request
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
            "receiving_good_id" => "required|max:255",
            "status" => "required|max:255",
            "quantity" => "required|integer",
            "quantity_package" => "required|integer",
            "product_id" => "required",
        ];
    }
    
    public function updateRules():array
    {
        return [
            "receiving_good_id" => "required|max:255",
            "status" => "required|max:255",
            "quantity" => "required|integer",
            "quantity_package" => "required|integer",
            "product_id" => "required",
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
