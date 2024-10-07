<?php

namespace Modules\PickupOrder\Http\Requests;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class DeliveryPickupOrderRequest extends Request
{
    use ResponseHandler;
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules(): array
    {
        return [];
    }

    public function updateRules(): array
    {
        return [];
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

    protected function failedValidation(Validator $validator){
        $errors = $validator->errors();
        $response = $this->response('04', 'invalid data send', $errors->messages());
        throw new HttpResponseException($response);
    }

    public function messages()
    {
        return [];
    }
}
