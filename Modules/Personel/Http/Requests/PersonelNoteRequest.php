<?php

namespace Modules\Personel\Http\Requests;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PersonelNoteRequest extends Request
{
    use ResponseHandler;


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules() : array
    {
        return [
            "user_id" => "required",
            "personel_id" => "required",
            "note" => "string|max:10000",
            "type" => "required",
        ];
    }
    
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function updateRules() : array
    {
        return [
            "user_id" => "max:40",
            "personel_id" => "max:40",
            "note" => "string|max:10000",
            "type" => "max:9",
            "status" => "max:20",
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

    protected function failedValidation(Validator $validator){
        $errors = $validator->errors();
        $response = $this->response("04", "invalid data send", $errors);
        throw new HttpResponseException($response);
    }
}
