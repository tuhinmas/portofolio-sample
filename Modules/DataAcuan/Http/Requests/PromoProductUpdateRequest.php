<?php

namespace Modules\DataAcuan\Http\Requests;

use App\Traits\ResponseHandler;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\DataAcuan\Entities\Promo;
use Illuminate\Support\Str;

class PromoProductUpdateRequest extends FormRequest
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
            "type" => "required|in:nominal,persen",
            "discount" => "required",
            "calculate" => "required|in:before,after",
            "min_unit" => "required",
            "min_total" => "required",
            "is_contest" => "required|in:0,1"
        ];
    }

    public function data(): array
    {
        $allData = $this->all();
        unset($allData['product_id']);
        $promo = Promo::find($this->promo_id);
        if ($promo) {
            $arrayToUpdate = json_decode($promo->attributes, true);
            $inputData = $this->all();

            $productId = $inputData['product_id'];
            $id = $this->product_promo_id;

            $productSame = false;
            foreach ($arrayToUpdate as $key => $value) {
                foreach ($value as $i) {
                    if ($key == $productId && $i['id'] == $id) {
                        $productSame = true;
                    }
                }
            }

            if ($productSame) {
                if (isset($arrayToUpdate[$productId])) {
                    $found = false;
                    foreach ($arrayToUpdate[$productId] as &$item) {
                        if ($item['id'] === $id) {
                            // Update the values with the input data
                            $item['type'] = $inputData['type'];
                            $item['discount'] = $inputData['discount'];
                            $item['min_unit'] = $inputData['min_unit'];
                            $item['calculate'] = $inputData['calculate'];
                            $item['min_total'] = $inputData['min_total'];
                            $item['is_contest'] = $inputData['is_contest'];
                            $found = true;
                            break; // Exit the loop once the update is done
                        }
                    }
                    if (!$found) {
                        unset($arrayToUpdate[$productId]);
                    }
                } else {
                    $arrayToUpdate[$productId] = [
                        [
                            "id" => uniqid(), 
                            "type" => $inputData['type'],
                            "discount" => $inputData['discount'],
                            "min_unit" => $inputData['min_unit'],
                            "calculate" => $inputData['calculate'],
                            "min_total" => $inputData['min_total'],
                            "is_contest" => $inputData['is_contest'],
                            "purchase_type" => $inputData['purchase_type'],
                        ]
                    ];
                }
            }else{
                $arrayToUpdate[$productId][] = [
                    "id" => uniqid(), 
                    "type" => $inputData['type'],
                    "discount" => $inputData['discount'],
                    "min_unit" => $inputData['min_unit'],
                    "calculate" => $inputData['calculate'],
                    "min_total" => $inputData['min_total'],
                    "is_contest" => $inputData['is_contest'],
                    "purchase_type" => $inputData['purchase_type'],
                ];

                foreach ($arrayToUpdate as $key => &$value) {
                    foreach ($value as $subKey => $subValue) {
                        if ($subValue['id'] === $id) {
                            unset($value[$subKey]);
                        }
                    }
                }
                
                $arrayToUpdate = array_filter($arrayToUpdate);
            }
            
            return $arrayToUpdate;
        }
    }

    private function resetArray($data)
    {
        $final = [];
        foreach ($data as $key => $row) {
            $final[$key] = array_values($row);
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
                            if ($this->product_id == $key && $row['min_unit'] == $this->min_unit && $row['id'] != $this->product_promo_id) {
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
