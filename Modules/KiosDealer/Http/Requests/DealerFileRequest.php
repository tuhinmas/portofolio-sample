<?php

namespace Modules\KiosDealer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DealerFileRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            // 'attachment' => 'required|mimes:jpg,jpeg,png,bmp,gif,svg,webp,pdf,docx|max:4096',
            'attachment_name' => 'required',
            'file_name' => 'required'
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
