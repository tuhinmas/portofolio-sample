<?php

namespace Modules\DataAcuan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BusinessSectorRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'sektor_usaha' => 'required|max:30'
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
}
