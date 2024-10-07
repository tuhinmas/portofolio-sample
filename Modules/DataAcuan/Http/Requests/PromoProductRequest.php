<?php

namespace Modules\DataAcuan\Http\Requests;

use App\Traits\ResponseHandler;
use Faker\Core\Uuid;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\DataAcuan\Entities\Promo;
use Illuminate\Support\Str;

class PromoProductRequest extends FormRequest
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
            "product_id" => "required",
            "calculate" => "required|in:before,after",
            "type" => "required|in:nominal,persen",
            "discount" => "required",
            "min_unit" => "required",
            "min_total" => "required",
            "is_contest" => "required|in:0,1"
        ];
    }
    

    public function data(): array
    {
        $allData = $this->all();
        unset($allData['product_id']);
        $data = [
            "$this->product_id" => [
                array_merge($allData, [
                    'id' => Str::uuid()->toString()
                ])
            ]
        ];

        $promo = Promo::find($this->promo_id);
        if ($promo) {
            $attributes = json_decode($promo->attributes, true);
            if ($attributes) {
                if (isset($attributes[$this->product_id])) {
                    $attributes[$this->product_id][] = array_merge([
                        'id' => Str::uuid()->toString()
                    ], $allData);
                }else{
                    $attributes = array_merge($attributes, $data);
                }

                return $attributes;
            }
        }

        return $data;
    }

    public function authorize()
    {
        return true;
    }

    public function messages()
    {
        return [
            "product_id.required" => "The product ID is required.",
            "type.required" => "The type is required.",
            "type.in" => "Invalid type selected. only nomina, persen",
            "discount.required" => "The discount is required.",
            "calculate.required" => "The calculation method is required.",
            "calculate.in" => "Invalid calculation method selected. only before, after",
            "min_unit.required" => "The minimum unit is required.",
            "is_contest.required" => "The contest flag is required.",
            "is_contest.in" => "Invalid contest flag value. only 1 or 0",
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $promo = Promo::find($this->promo_id);
            if ($promo) {
                $attributes = json_decode($promo->attributes, true);
                if ($attributes) {
                    foreach ($attributes as $key => $value) {
                        foreach ($value as $row) {
                            if ($this->product_id == $key && $row['min_unit'] == $this->min_unit) {
                                return $validator->errors()->add('custom_error_validate', [
                                    'message' => 'Tidak boleh melakuakan input data produk, dengan min unit yang sama',
                                ]);
                            }
                        }
                    }
                }
            }
        });
    }

    protected function failedValidation(Validator $validation)
    {
        $errors = $validation->errors();
        $response = $this->response('04', 'invalid data send', $errors);
        throw new HttpResponseException($response);
    }
}
