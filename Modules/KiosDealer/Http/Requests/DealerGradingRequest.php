<?php

namespace Modules\KiosDealer\Http\Requests;

use Orion\Http\Requests\Request;
use Illuminate\Foundation\Http\FormRequest;

class DealerGradingRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            
            'grading_id' => 'required',
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
