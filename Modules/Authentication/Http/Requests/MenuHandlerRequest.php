<?php

namespace Modules\Authentication\Http\Requests;

use App\Traits\ResponseHandler;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Orion\Http\Requests\Request;

class MenuHandlerRequest extends Request
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
            "title" => "required|max:255",
            "icon" => "required|max:255",
            "role" => "required|max:255",
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
            "title" => "max:255",
            "icon" => "max:255",
            "role" => "max:255",
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
        $response = $this->response('04', 'invalid data send', $errors->messages(), 422);
        throw new HttpResponseException($response);
    }
}
