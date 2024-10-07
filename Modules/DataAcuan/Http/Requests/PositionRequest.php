<?php

namespace Modules\DataAcuan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PositionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'jabatan' => 'required|max:200',
            'divisi' => 'required',
            'deskripsi_pekerjaan' => 'required',
            'definisi_pekerjaan' => 'required',
            'spesifikasi_pekerjaan' => 'required',
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
