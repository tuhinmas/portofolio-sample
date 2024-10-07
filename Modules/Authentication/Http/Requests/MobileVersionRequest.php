<?php

namespace Modules\Authentication\Http\Requests;

use Orion\Http\Requests\Request;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Authentication\Rules\ApkEnvironmentRule;
use Modules\Authentication\Rules\ApkVersionUniqueRule;

class MobileVersionRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules(): array
    {
        return [
            "version" => [
                "required",
                "max:9999999",
                new ApkVersionUniqueRule($this->environment)
            ],
            "environment" => [
                "required",
                "string",
                "max:255"
            ],
            "note" => [
                "required",
                "max:999"
            ],
            "link" => [
                "required",
                "url",
                new ApkEnvironmentRule
            ]
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
