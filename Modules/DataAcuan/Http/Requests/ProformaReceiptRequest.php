<?php

namespace Modules\DataAcuan\Http\Requests;

use App\Traits\ResponseHandler;
use Illuminate\Validation\Rule;
use Orion\Http\Requests\Request;

class ProformaReceiptRequest extends Request
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
            "siup" => [
                "required",
                "min:1",
                "max:255",
            ],
            "npwp" => [
                "required",
                "min:1",
                "max:255",
            ],
            "tdp" => [
                "min:1",
                "max:255",
            ],
            "ho" => [
                "min:1",
                "max:255",
            ],
            "nib" => [
                "required",
                "min:1",
                "max:255",
            ],
            "payment_info" => [
                "min:1",
                "max:9999999999",
            ],
            "note_receving" => [
                "min:1",
                "max:9999999999",
            ],
            "note_sop" => [
                "min:1",
                "max:9999999999",
            ],
            "note" => [
                "min:1",
                "max:9999999999",
            ],
            "company_name" => "max:255",
            "company_address" => "required",
            "company_telephone" => "required|max:255",
            "company_hp" => "required|max:255",
            "company_email" => "required|max:255",
            "logo_link" => "required",
            "image_header_link" => "required",
            "image_footer_link" => "required",
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
            "siup" => [
                "min:1",
                "max:255",
                "nullable",
                Rule::requiredIf(function () {
                    return in_array($this->receipt_for, [1, 2, 3, 4]);
                }),
            ],
            "npwp" => [
                "min:1",
                "max:255",
                "nullable",
                Rule::requiredIf(function () {
                    return in_array($this->receipt_for, [1, 2, 3, 4]);
                }),
            ],
            "nib" => [
                "min:1",
                "max:255",
                "nullable",
                Rule::requiredIf(function () {
                    return in_array($this->receipt_for, [1, 2, 3, 4]);
                }),
            ],
            "tdp" => [
                "min:1",
                "max:255",
            ],
            "ho" => [
                "min:1",
                "max:255",
            ],
            "payment_info" => [
                "min:1",
                "max:9999999999",
            ],
            "note_receving" => [
                "min:1",
                "max:9999999999",
            ],
            "note_sop" => [
                "min:1",
                "max:9999999999",
            ],
            "note" => [
                "min:1",
                "max:9999999999",
            ],
            "receipt_for" => [
                "required",
                "numeric",
                "digits:1",
            ],
            "company_name" => "max:255",
            "company_address" => [
                "nullable",
                Rule::requiredIf(function () {
                    return in_array($this->receipt_for, [1, 2, 3, 4, 5, 6]);
                }),
            ],
            "company_telephone" => [
                "nullable",
                "max:255",
                Rule::requiredIf(function () {
                    return in_array($this->receipt_for, [1, 2, 3, 4, 5, 6]);
                }),
            ],
            "company_hp" => [
                "nullable",
                "max:255",
                Rule::requiredIf(function () {
                    return in_array($this->receipt_for, [1, 2, 3, 4, 5, 6]);
                }),
            ],
            "company_email" => [
                "nullable",
                "max:255",
                Rule::requiredIf(function () {
                    return in_array($this->receipt_for, [1, 2, 3, 4, 5, 6]);
                }),
            ],
            "logo_link" => [
                "nullable",
                Rule::requiredIf(function () {
                    return in_array($this->receipt_for, [1, 2, 3, 4, 5, 6]);
                }),
            ],
            "image_header_link" => [
                "nullable",
                Rule::requiredIf(function () {
                    return in_array($this->receipt_for, [1, 2, 3, 4, 5, 6]);
                }),
            ],
            "image_footer_link" => [
                "nullable",
                Rule::requiredIf(function () {
                    return in_array($this->receipt_for, [1, 2, 3, 4, 5, 6]);
                }),
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
