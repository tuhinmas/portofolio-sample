<?php

namespace Modules\DistributionChannel\Http\Requests;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class DeliveryOrderFileRequest extends Request
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
            "id_delivery_orders" => "required",
            "document" => "required",
            "caption" => "required",
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
            "id_delivery_orders" => "required",
            "document" => "required",
            "caption" => "required",
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

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();
        $response = $this->response("04", "invalid data send", $errors, 422);
        throw new HttpResponseException($response);
    }
}
