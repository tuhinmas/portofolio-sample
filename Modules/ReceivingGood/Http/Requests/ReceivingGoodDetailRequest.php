<?php

namespace Modules\ReceivingGood\Http\Requests;

use App\Traits\ResponseHandler;
use Illuminate\Validation\Rule;
use Modules\ReceivingGood\Rules\ReceivingGoodDetailProductRule;
use Orion\Http\Requests\Request;

class ReceivingGoodDetailRequest extends request
{
    use ResponseHandler;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules(): array
    {
        $validation = [
            "receiving_good_id" => "required",
            "user_id" => "required",
        ];

        if ($this->resources) {
            $validation_custom = [
                "product_id" => [
                    new ReceivingGoodDetailProductRule($this),
                    Rule::requiredIf(function () {
                        foreach ($this->resources as $resource) {
                            if (!array_key_exists("promotion_good_id", $resource)) {
                                return true;
                            }
                        }
                    }),
                ],
                "promotion_good_id" => [
                    new ReceivingGoodDetailProductRule($this),
                    Rule::requiredIf(function () {
                        foreach ($this->resources as $resource) {
                            if (!array_key_exists("product_id", $resource)) {
                                return true;
                            }
                        }
                    }),
                ],
            ];
        } else {
            $validation_custom = [
                "product_id" => [
                    new ReceivingGoodDetailProductRule($this),
                    Rule::requiredIf(function () {
                        return empty($this->promotion_good_id);
                    }),
                ],
                "promotion_good_id" => [
                    new ReceivingGoodDetailProductRule($this),
                    Rule::requiredIf(function () {
                        return empty($this->product_id);
                    }),
                ],
            ];
        }

        $validation_final = array_merge($validation, $validation_custom);

        return $validation_final;
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
