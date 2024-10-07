<?php

namespace Modules\Invoice\Http\Requests;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class EntrusmentPaymentRequest extends Request
{
    use ResponseHandler;
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function commonRules() : array
    {
        return [
            "invoice_id" => "required",
            "nominal" => "required",
            "user_id" => "required",
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
