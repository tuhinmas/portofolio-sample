<?php

namespace Modules\DataAcuan\Http\Requests;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Contracts\Validation\Validator;
use Modules\DataAcuan\Rules\UniqueFeeProductRule;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\DataAcuan\Rules\UniqueFeeProductOnUpdate;
use Modules\DataAcuan\Rules\UniqueFeeProductTargetRule;
use Modules\DataAcuan\Rules\UniqueFeeProductRegulerRule;

class FeeRequest extends Request
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
            "year" => [
                "required",
                new UniqueFeeProductRule(
                    $this->year, 
                    $this->product_id, 
                    $this->quantity, 
                    $this->fee, 
                    $this->type, 
                    $this->quartal),
            ],
            'quartal' => "required|numeric|between:1,4",
            "type" => "required",
            "product_id" => [
                "required",
                "exists:products,id",
                "max:255",
            ],
            "quantity" => "required|numeric|min:1|max:100000000000",
            "fee" => "required|numeric|min:1|max:100000000000",
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
            "year" => [
                "max:9999",
                new UniqueFeeProductOnUpdate(
                    $this->year, 
                    $this->product_id, 
                    $this->quantity, 
                    $this->fee, 
                    $this->type, 
                    $this->quartal
                ),
            ],
            'quartal' => "required|numeric|between:1,4",
            "type" => "max:9",
            "product_id" => "exists:products,id|max:255",
            "qty" => "numeric|min:1|max:100000000000",
            "fee" => "numeric|min:1|max:100000000000",
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
