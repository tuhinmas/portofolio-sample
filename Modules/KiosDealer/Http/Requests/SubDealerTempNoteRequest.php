<?php

namespace Modules\KiosDealer\Http\Requests;

use App\Traits\ResponseHandler;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Orion\Http\Requests\Request;

class SubDealerTempNoteRequest extends Request
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
            'sub_dealer_temp_id' => 'required|string|max:255',
            'personel_id' => 'required|string|max:255',
            'note' => 'string|min:3|max:5000',
            'status' => 'required|string|max:255',
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
            'note' => 'string|min:3|max:5000',
            'status' => 'required|string|max:255',
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
        $response = $this->response("04", "invalid data send", $errors, 422);
        throw new HttpResponseException($response);
    }
}
