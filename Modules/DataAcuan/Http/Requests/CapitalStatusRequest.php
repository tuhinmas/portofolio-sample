<?php

namespace Modules\DataAcuan\Http\Requests;

use App\Traits\ResponseHandler;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CapitalStatusRequest extends FormRequest
{
    use ResponseHandler;
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            "name" => "required|min:3|max:50"
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
        $response = $this->response('04', 'invalid data send', $errors);
        throw new HttpResponseException($response);
    }
}
