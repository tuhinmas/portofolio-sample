<?php

namespace Modules\DataAcuan\Http\Requests;

use Orion\Http\Requests\Request;

class DriverRequest extends Request
{
        /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules(): array
    {
        return [
            "transportation_type" => "required",
            "police_number" => "required|unique:drivers,police_number,NULL,id,deleted_at,NULL|max:12",
            "id_driver" => "required",
            "capacity" => "required",
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
            "transportation_type" => "required",
            "police_number" => "required",
            "id_driver" => "required",
            "capacity" => "required",
        ];
    }
    
    public function messages()
    {
        return [
            'police_number.unique' => 'Police Number Must be Unique'
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
