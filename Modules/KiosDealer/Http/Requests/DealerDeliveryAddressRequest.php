<?php

namespace Modules\KiosDealer\Http\Requests;

use Orion\Http\Requests\Request;

class DealerDeliveryAddressRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function storeRules(): array
    {
        return [
            "province_id" => [
                "required",
                "max:36",
                "min:2",
            ],
            "district_id" => [
                "required",
                "max:36",
                "min:7",
            ],
            "postal_code" => [
                "max:255",
                "min:5",
            ],
            "gmaps_link" => [
                "max:99999",
                "min:5",
                "nullable"
            ],
            "dealer_id" => [
                "required",
                "max:36",
                "min:5",
            ],
            "telephone" => [
                "max:255",
                "min:5",
            ],
            "longitude" => [
                "max:255",
                "min:5",
                "nullable"
            ],
            "is_active" => [
                "boolean",
            ],
            "latitude" => [
                "max:255",
                "min:5",
                "nullable"
            ],
            "address" => [
                "required",
                "max:999",
                "min:5",
            ],
            "city_id" => [
                "required",
                "max:36",
                "min:4",
            ],
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
            "province_id" => [
                "max:36",
                "min:2",
            ],
            "district_id" => [
                "max:36",
                "min:2",
            ],
            "postal_code" => [
                "max:255",
                "min:5",
            ],
            "dealer_id" => [
                "max:36",
                "min:5",
            ],
            "telephone" => [
                "max:255",
                "min:5",
            ],
            "longitude" => [
                "max:255",
                "min:5",
            ],
            "is_active" => [
                "boolean",
            ],
            "latitude" => [
                "max:255",
                "min:5",
            ],
            "address" => [
                "max:999",
                "min:5",
            ],
            "city_id" => [
                "max:36",
                "min:2",
            ],
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
