<?php

namespace Modules\DataAcuan\Http\Requests;

use App\Traits\ResponseHandler;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Modules\DataAcuan\Entities\PaymentDayColor;
use Orion\Http\Requests\Request;

class ProductV2Request extends Request
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
            "name" => "required|min:3|max:50",
            "size" => "required",
            "unit" => "required",
            "type" => "required",
            "category_id" => "required",
            "weight" => "required"

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
            "name" => "required|min:3|max:50",
            "size" => "required",
            "unit" => "required",
            "type" => "required",
            "category_id" => "required",
            "weight" => "required"
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
        // dd($errors->messages());
        $response = $this->response(
            "04",
            "invalid data send",
            $errors->messages(),
            422
        );
        throw new HttpResponseException($response);
    }

    public function messages()
    {
        return [
        ];
    }
}