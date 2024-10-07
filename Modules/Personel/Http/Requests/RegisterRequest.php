<?php

namespace Modules\Personel\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Personel\Rules\PersonelPositionRule;

class RegisterRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:100',
            'born_place' => 'required|string|max:100',
            'born_date' => 'required|date|max:100',
            'position_id' => [
                'required',
                new PersonelPositionRule($this->supervisor_id),
            ],
            'religion_id' => 'required',
            'gender' => 'required',
            'citizenship' => 'required',
            'organisation_id' => 'required',
            'identity_card_type' => 'required', //id
            'identity_number' => 'required',
            'blood_group' => 'required', 
            'join_date' => [function ($attribute, $value, $fail) {
                $endDate = date('Y-m-d', strtotime($this->input('resign_date')));
                $today = now()->format('Y-m-d');
                
                // if ($value > $today) {
                //     $fail('Tanggal mulai harus lebih awal atau sama dengan tanggal hari ini.');
                // }
                
                if (!empty($this->input('resign_date'))) {
                    if ($endDate && $value > $endDate) {
                        $fail('Tanggal mulai harus lebih awal atau sama dengan tanggal akhir.');
                    }
                }
            }],
            'resign_date' => ['nullable', 'after_or_equal:join_date'],
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

    public function messages()
    {
        return [
            'join_date.required' => 'Tanggal mulai harus diisi.',
            'join_date.date' => 'Tanggal mulai harus berupa tanggal yang valid.',
            'resign_date.required' => 'Tanggal akhir harus diisi.',
            'resign_date.date' => 'Tanggal akhir harus berupa tanggal yang valid.',
            'resign_date.after_or_equal' => 'Tanggal akhir harus setelah atau sama dengan tanggal mulai.',
        ];
    }
}
