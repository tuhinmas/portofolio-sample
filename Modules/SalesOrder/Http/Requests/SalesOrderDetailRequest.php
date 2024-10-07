<?php

namespace Modules\SalesOrder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SalesOrderDetailRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'store' => 'required',
            'product' => 'required',
            'quantity' => 'required|max:50000'
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
