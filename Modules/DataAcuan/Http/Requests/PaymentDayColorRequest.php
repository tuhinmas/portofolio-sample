<?php

namespace Modules\DataAcuan\Http\Requests;

use App\Traits\ResponseHandler;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Modules\DataAcuan\Entities\PaymentDayColor;
use Orion\Http\Requests\Request;

class PaymentDayColorRequest extends Request
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
            "min_days" => 'required',
            "max_days" => 'gt:min_days',
            "bg_color" => [
                'required', 'max:6',
                Rule::unique('payment_day_colors')->whereNull('deleted_at'),
            ],
            "text_color" => 'required|max:6'

        ];
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function updateRules(): array
    {
        $id = $this->route('payment_day_color');
        return [
            "min_days" => 'required|numeric',
            "max_days" => 'nullable|numeric|gt:min_days',
            "bg_color" => [
                'required',
                'max:6',
                Rule::unique('payment_day_colors')->whereNull('deleted_at')->ignore($id)
            ],
            "text_color" => 'required|max:6'
        ];
    }

    public function withValidator($validator)
    {
        // Menambahkan validasi setelah rules terpenuhi
        $validator->after(function ($validator) {

            // Mengecek apakah nama pengguna sudah ada di database
            $paymentDayColorId = Route::current()->parameter("payment_day_color");

            if ($this->isMethod('post')) {
                $PaymentDayColor = PaymentDayColor::select("id", "min_days", "max_days")->get();
            } elseif ($this->isMethod('put')) {
                $PaymentDayColor = PaymentDayColor::select("id", "min_days", "max_days")->where("id", "!=", $paymentDayColorId)->get();
                // dd($PaymentDayColor);
            }

            if ($this->min_days || $this->max_days) {
                foreach ($PaymentDayColor as $data) {
                    if ($data->max_days) {
                        // if ($this->max_days) {
                        if ($this->min_days >= $data->min_days && $this->min_days <= $data->max_days) {
                            $validator->errors()->add('min_days', 'min_days.cannot_intersact');
                        } elseif ($this->max_days != null && ($this->max_days <= $data->max_days && $this->max_days >= $data->min_days)) {
                            $validator->errors()->add('max_days', 'max_days.cannot_intersact');
                        }
                        // }
                    }
                }

                foreach ($PaymentDayColor as $key => $value) {
                    if ($value->max_days === null) {
                        if (($this->min_days >= $value->min_days) || $this->max_days === null) {
                            if ($this->min_days >= $value->min_days) {
                                $validator->errors()->add('min_days', 'min_days.cannot_intersact');
                            }
                            if ($this->max_days === null) {
                                $validator->errors()->add('max_days', 'max_days.cannot_duplication_null');
                            }
                        }
                        break; // Menghentikan loop setelah menemukan nilai null pertama
                    }
                }
            }
            //}
        });
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
        // dd($errors->messages());
        $response = $this->response(
            "04",
            "invalid data send",
            $errors->messages(),
            422
        );
        throw new HttpResponseException($response);
    }

    public function messages()
    {
        return [
            'min_days.required' => 'min_days.required',
            'min_days.numeric' => 'min_days.numeric',
            'max_days.gt' => 'max_days.gt',
            'bg_color.unique' => 'bg_color.unique',
            'bg_color.max' => 'bg_color.max',
            'text_color.max' => 'bg_color.max'
        ];
    }
}
