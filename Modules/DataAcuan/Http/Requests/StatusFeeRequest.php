<?php

namespace Modules\DataAcuan\Http\Requests;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StatusFeeRequest extends Request
{
    use ResponseHandler;
    
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules() :array
    {
        return [
            "name" => "required",
            "percentage" => "required"
        ];
    }
    
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function updateRules() :array
    {
        return [
            "name" => "max:255",
            "percentage" => "max:255"
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
        $response = $this->response("04", "invalid data send", $errors);
        throw new HttpResponseException($response);
    }
}
