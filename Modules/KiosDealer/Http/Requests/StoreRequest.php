<?php

namespace Modules\KiosDealer\Http\Requests;

use App\Traits\ResponseHandler;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\KiosDealer\Rule\NoTelpKiosRule;

class StoreRequest extends FormRequest
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
            'name' => 'required|string|max:100',
            'address' => 'required|string|max:255',
            'telephone' => [
                'digits_between:6,15',
                new NoTelpKiosRule($this->telephone, $this->personel_id)
            ],
            'kecamatan' => "required",
            'kabupaten' => "required",
            'provinsi' => "required",
            // "latitude" => [
            //     "required",
            // ],
            // "longitude" => [
            //     "required",
            // ],
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
        $response = $this->response('04', 'invalid data send', $errors->messages());
        throw new HttpResponseException($response);
    }
}
