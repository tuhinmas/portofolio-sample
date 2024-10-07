<?php

namespace Modules\DataAcuan\Http\Requests;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PromoRequest extends Request
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
            "name" => "required|min:3|max:50|unique:promos,name,NULL,id,deleted_at,NULL",
            "date_start" => ['required', function ($attribute, $value, $fail) {
                $endDate = date('Y-m-d', strtotime($this->input('date_end')));
                $tomorow = date('Y-m-d', strtotime("+1 days"));
                
                if ($value < $tomorow) {
                    $fail('tanggal awal minimal adalah today+1');
                }
                
                if (!empty($this->input('date_end'))) {
                    if ($endDate && $value > $endDate) {
                        $fail('Tanggal mulai harus lebih awal atau sama dengan tanggal akhir.');
                    }
                }
            }],
            "date_end"  => ['required', 'after_or_equal:date_start'],
        ];
    }

    public function updateRules(): array
    {
        $promoId = $this->promo;
        return [
            "name" => "required|min:3|max:50|unique:promos,name,$promoId,id,deleted_at,NULL",
            "date_start" => ['required', function ($attribute, $value, $fail) {
                $endDate = date('Y-m-d', strtotime($this->input('date_end')));
                $tomorow = date('Y-m-d', strtotime("+1 days"));
                
                if ($value < $tomorow) {
                    $fail('tanggal awal minimal adalah today+1');
                }
                
                if (!empty($this->input('date_end'))) {
                    if ($endDate && $value > $endDate) {
                        $fail('Tanggal mulai harus lebih awal atau sama dengan tanggal akhir.');
                    }
                }
            }],
            "date_end"  => ['required', 'after_or_equal:date_start'],
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
        $response = $this->response('04', 'invalid data send', $errors->messages());
        throw new HttpResponseException($response);
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
