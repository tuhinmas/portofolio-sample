<?php

namespace Modules\DataAcuan\Http\Requests;

use App\Traits\ResponseHandler;
use Orion\Http\Requests\Request;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class FeePositionRequest extends Request
{
    use ResponseHandler;

    
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules() : array
    {
        return [
            "position_id" => "required",
            "fee" => "required",
            "follow_up" => "required",
            "fee_cash" => "required",
            "fee_cash_minimum_order" => "required",
        ];
    }
   
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function updateRules() : array
    {
        return [
            "date_start" => "required",
            "position" => "max:255",
            "fee" => "max:255",
            "follow_up" => "max:255",
            "fee_cash" => "max:255",
            "fee_cash_minimum_order" => "max:255",
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
