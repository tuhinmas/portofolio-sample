<?php

namespace Modules\DataAcuan\Http\Requests;

use Orion\Http\Requests\Request;
use Illuminate\Foundation\Http\FormRequest;

class PointProductRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules() : array
    {
        return [
            "year" => "required|max:255",
            "product_id" => "required|max:255",
            "minimum_quantity" => "required|max:255",
            "point" => "required|max:255",
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
            "year" => "max:255",
            "product_id" => "max:255",
            "minimum_quantity" => "max:255",
            "point" => "max:255",
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
