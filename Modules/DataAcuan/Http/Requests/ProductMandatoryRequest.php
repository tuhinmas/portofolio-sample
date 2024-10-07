<?php

namespace Modules\DataAcuan\Http\Requests;

use App\Traits\ResponseHandler;
use Illuminate\Validation\Rule;
use Orion\Http\Requests\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\Validation\Validator;
use Modules\DataAcuan\Rules\MetricProductRule;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductMandatoryRequest extends Request
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
            "product_id" => [
                "required",
                "array",
                "max:255",
                new MetricProductRule($this->product_id),
                Rule::unique('product_mandatory_pivots')
                    ->whereIn("product_id", $this->product_id)
                    ->where("period_date", $this->period_date),

                Rule::exists("product_group_members")
                    ->where("product_group_id", $this->product_group_id)
                    ->whereIn("product_id", $this->product_id)
                    ->whereNull("deleted_at")
            ],
            "period_date" => "required|digits:4|integer|min:1900|max:9999",
            "product_group_id" => [
                "required",
                "max:255",
                Rule::unique('product_mandatories')
                    ->where("product_group_id", $this->product_group_id)
                    ->where("period_date", $this->period_date)
                    ->whereNull("deleted_at"),
            ],
            "target" => "required|max:99999999|integer",
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function updateRules(): array
    {
        return [
            "product_id" => [
                "required",
                "array",
                "max:255",
                Rule::unique('product_mandatory_pivots')
                    ->whereNotIn("product_id", $this->product_id)
                    ->where("period_date", $this->period_date),
                Rule::exists("product_group_members")
                    ->where("product_group_id", $this->product_group_id)
                    ->whereIn("product_id", $this->product_id),
            ],
            "period_date" => "required",
            "product_group_id" => "max:255",
            "target" => "max:99999999|integer",
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

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();
        $response = $this->response("04", "invalid data send", $errors, 422);
        throw new HttpResponseException($response);
    }
}
