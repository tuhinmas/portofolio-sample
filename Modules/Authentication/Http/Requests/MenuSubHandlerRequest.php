<?php

namespace Modules\Authentication\Http\Requests;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class MenuSubHandlerRequest extends Request
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
            "menu_id" => "required|max:155",
            "screen" => "required|max:155",
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
            "menu_id" => "max:155",
            "screen" => "max:155",
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
        $response = $this->response('04', 'invalid data send', $errors->messages(), 422);
        throw new HttpResponseException($response);
    }
}
