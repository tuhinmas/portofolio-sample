<?php

namespace Modules\KiosDealer\Http\Requests;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CoreFarmerTempRequest extends Request
{
    use ResponseHandler;
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function commonRules() :array
    {
        return [
            "name" => "required|string|min:1|max:200",
            "address" => "required|min:1|max:100",
            'telephone' => "required|digits_between:6,15",
            "store_temp_id" => "required|max:50",
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
        $response = $this->response('04', 'invalid data send', $errors->messages());
        throw new HttpResponseException($response);    
    }
}
