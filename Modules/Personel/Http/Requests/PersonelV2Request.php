<?php

namespace Modules\Personel\Http\Requests;

use Orion\Http\Requests\Request;
use Illuminate\Foundation\Http\FormRequest;

class PersonelV2Request extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules() : array
    {
        return [
            //
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
